<?php
/**
 * @link      http://github.com/zendframework/zend-skeleton-installer for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\SkeletonInstaller;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Installer as ComposerInstaller;
use Composer\IO\IOInterface;
use Composer\Package\AliasPackage;
use Composer\Package\Link;
use Composer\Package\Version\VersionParser;

/**
 * Prompt for and install optional packages.
 */
class OptionalPackagesInstaller
{
    use ComposerJsonRetrievalTrait;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Prompt for and install optional packages
     */
    public function __invoke()
    {
        // Locate and return valid optional packages
        $optionalPackages = Collection::create($this->getOptionalDependencies())
            ->filter([OptionalPackage::class, 'isValidSpec']);

        // If none are found, do nothing.
        if (0 === $optionalPackages->count()) {
            return;
        }

        // Prompt for minimal install
        if ($this->requestMinimalInstall()) {
            // If a minimal install is requested, do nothing.
            return;
        }

        // Prompt for each package, and filter accordingly
        $packagesToInstall = $optionalPackages
            ->map(function ($spec) {
                return new OptionalPackage($spec);
            })
            ->filter($this->promptForPackage());

        // If no optional packages were selected, do nothing.
        if (0 === $packagesToInstall->count()) {
            $this->io->write('<info>    No optional packages selected to install</info>');
            return;
        }

        $this->io->write('<info>Updating root package</info>');

        // Run an installer update
        $this->io->write('<info>    Running an update to install optional packages</info>');
        $package = $this->updateRootPackage($this->composer->getPackage(), $packagesToInstall);
        $result = $this->createInstaller($package, $packagesToInstall)->run();

        if (0 !== $result) {
            $this->io->write('<error>Error installing optional packages. Run with verbosity to debug');
            return;
        }

        // Update the composer.json
        $this->io->write('<info>    Updating composer.json</info>');
        $this->updateComposerJson($packagesToInstall);

        // Update the modules list
        $this->io->write('<info>    Updating module configuration</info>');
        $this->injectModulesIntoConfiguration($packagesToInstall->reject(function ($package) {
            return empty($package->getModule());
        }));
    }

    /**
     * Retrieve list of optional dependencies.
     *
     * Looks for a extra.zend-skeleton-installer key with an array value,
     * returning it if found, or an empty array otherwise.
     *
     * @return array
     */
    private function getOptionalDependencies()
    {
        $package = $this->composer->getPackage();
        $extra = $package->getExtra();

        return isset($extra['zend-skeleton-installer']) && is_array($extra['zend-skeleton-installer'])
            ? $extra['zend-skeleton-installer']
            : [];
    }

    /**
     * Prompt the user for a mimimal install.
     *
     * @return bool
     */
    private function requestMinimalInstall()
    {
        $question = [
            "\n    <question>Do you want a minimal install (no optional packages)?</question> <comment>Y/n</comment>\n",
        ];

        while (true) {
            $answer = $this->io->ask($question, 'y');
            $answer = strtolower($answer);

            if ('n' === $answer) {
                return false;
            }

            if ('y' !== $answer) {
                return true;
            }

            $this->io->write('<error>Invalid answer</error>');
        }
    }

    /**
     * Create the callback for emitting and handling a package prompt.
     *
     * @return callable
     */
    private function promptForPackage()
    {
        return function (OptionalPackage $package) {
            $question = [sprintf(
                "\n    <question>%s</question> <comment>y/N</comment>\n",
                $package->getPrompt()
            )];

            while (true) {
                $answer = $this->io->ask($question, 'n');
                $answer = strtolower($answer);

                if ('n' === $answer) {
                    return false;
                }

                if ('y' !== $answer) {
                    return true;
                }

                $this->io->write('<error>Invalid answer</error>');
            }
        };
    }

    /**
     * Update the composer.json definition.
     *
     * @param Collection $packagesToInstall
     */
    private function updateComposerJson(Collection $packagesToInstall)
    {
        $composerJson = $this->getComposerJson();
        $json = $packagesToInstall->reduce($this->updateComposerRequirement(), $composerJson->read());
        $composerJson->write($json);
    }

    /**
     * Creates callback for updating the composer definition.
     *
     * @return callable
     */
    private function updateComposerRequirement()
    {
        return function ($composer, $package) {
            $key = $package->isDev() ? 'require-dev' : 'require';
            $composer[$key][$package->getName()] = $package->getConstraint();
            return $composer;
        };
    }

    /**
     * Update the root package definition
     *
     * @param \Composer\Package\RootPackage $package
     * @param Collection $packagesToInstall
     * @return \Composer\Package\RootPackage
     */
    private function updateRootPackage($package, Collection $packagesToInstall)
    {
        $requires = $packagesToInstall->reduce($this->updateRootPackageRequirement(), $package->getRequires());
        $package->setRequires($requires);
        return $package;
    }

    /**
     * Creates a callback for updating the root package requirements.
     *
     * @return callable
     */
    private function updateRootPackageRequirement(array $requires)
    {
        $versionParser = new VersionParser();
        return function ($requires, $package) use ($versionParser) {
            $name = $package->getName();
            $constraint = $package->getConstraint();
            $description = $package->isDev()
                ? 'requires for development'
                : 'requires';

            $requires[$name] = new Link(
                '__root__',
                $name,
                $versionParser->parseConstraints($constraint),
                $description,
                $constraint
            );

            return $requires;
        };
    }

    /**
     * Creates and returns a configured instance of the Composer installer.
     *
     * The instance has the following modifications:
     *
     * - It uses the updated root package
     * - It uses a new EventDispatcher, to prevent triggering already accumulated plugins
     * - It marks the operation as an update
     * - It specifies an update whitelist of only the new packages to install
     * - It disables plugins
     *
     * @param \Composer\Package\RootPackage
     * @param Collection $packagesToInstall
     * @return ComposerInstaller
     */
    private function createInstaller($package, Collection $packagesToInstall)
    {
        $installer = new ComposerInstaller(
            $this->io,
            $this->composer->getConfig(),
            $package,
            $this->composer->getDownloadManager(),
            $this->composer->getRepositoryManager(),
            $this->composer->getLocker(),
            $this->composer->getInstallationManager(),
            new EventDispatcher($this->composer, $this->io),
            $this->composer->getAutoloadGenerator()
        );
        $installer->setUpdate();
        $installer->disablePlugins();
        $installer->setUpdateWhitelist(
            $packagesToInstall->map(function ($package) {
                return $package->getName();
            })
            ->toArray()
        );

        return $installer;
    }

    /**
     * Inject modules into application configuration.
     *
     * This assumes that:
     *
     * - config/modules.config.php exists
     * - config/development.config.php.dist exists
     *
     * Modules marked as `isDev()` will be pushed into the development module
     * list, while all others will go into the general modules list.
     *
     * @param Collection $modulesToInstall
     * @return void
     */
    private function injectModulesIntoConfiguration(Collection $modulesToInstall)
    {
    }
}
