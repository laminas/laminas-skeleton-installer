<?php

/**
 * @see       https://github.com/laminas/laminas-skeleton-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-skeleton-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-skeleton-installer/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\SkeletonInstaller;

use Composer\Composer;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\IO\IOInterface;
use Composer\Package\AliasPackage;
use Composer\Repository\RepositoryInterface;

/**
 * Uninstall the plugin from the project.
 *
 * This class will uninstall the plugin from the project when invoked;
 * this includes running an uninstall operation on the project, as well
 * as removing it from the composer.json.
 */
class Uninstaller
{
    use ComposerJsonRetrievalTrait;

    const PLUGIN_NAME = 'laminas/laminas-skeleton-installer';

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
     * Run the uninstall operation.
     */
    public function __invoke()
    {
        $this->io->write(sprintf('<info>Removing %s...</info>', self::PLUGIN_NAME));
        $this->removePluginInstall();
        $this->removePluginFromComposer();
        $this->io->write('<info>    Complete!</info>');
    }

    /**
     * Remove the plugin installation itself.
     */
    private function removePluginInstall()
    {
        $installer = $this->composer->getInstallationManager();
        $repository = $this->composer->getRepositoryManager()->getLocalRepository();
        $package = $repository->findPackage(self::PLUGIN_NAME, '*');

        if (! $package) {
            $this->io->write('<info>    Package not installed; nothing to do.</info>');
            return;
        }

        $installer->uninstall($repository, new UninstallOperation($package));
        $this->io->write(sprintf('<info>    Removed plugin %s.</info>', self::PLUGIN_NAME));
        $this->updateLockFile($repository);
    }

    /**
     * Remove the plugin from the composer.json
     */
    private function removePluginFromComposer()
    {
        $this->io->write('<info>    Removing from composer.json</info>');

        $composerJson = $this->getComposerJson();
        $json = $composerJson->read();
        unset($json['require'][self::PLUGIN_NAME]);
        $composerJson->write($json);
    }

    /**
     * Update the lock file
     *
     * @param RepositoryInterface $repository
     */
    private function updateLockFile(RepositoryInterface $repository)
    {
        $locker = $this->composer->getLocker();
        $allPackages = Collection::create($repository->getPackages())
            ->reject(function ($package) {
                return self::PLUGIN_NAME === $package->getName();
            });

        $aliases = $allPackages->filter(function ($package) {
            return $package instanceof AliasPackage;
        });

        $devPackages = $allPackages->filter(function ($package) {
            return $package->isDev();
        });

        $packages = $allPackages->filter(function ($package) {
            return (! $package instanceof AliasPackage && ! $package->isDev());
        });

        $platformReqs = $locker->getPlatformRequirements(false);
        $platformDevReqs = array_diff($locker->getPlatformRequirements(true), $platformReqs);

        $result = $locker->setLockData(
            $packages->toArray(),
            $devPackages->toArray(),
            $platformReqs,
            $platformDevReqs,
            $aliases->toArray(),
            $locker->getMinimumStability(),
            $locker->getStabilityFlags(),
            $locker->getPreferStable(),
            $locker->getPreferLowest(),
            $locker->getPlatformOverrides()
        );

        if (! $result) {
            $this->io->write('<error>Unable to update lock file after removal of laminas-skeleton-installer</error>');
        }
    }
}
