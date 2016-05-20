<?php
/**
 * @link      http://github.com/zendframework/zend-skeleton-installer for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\SkeletonInstaller;

use Composer\Composer;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\IO\IOInterface;

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

    const PLUGIN_NAME = 'zendframework/zend-skeleton-installer';

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
}
