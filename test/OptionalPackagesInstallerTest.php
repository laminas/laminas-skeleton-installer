<?php
/**
 * @link      http://github.com/zendframework/zend-skeleton-installer for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\SkeletonInstaller;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\SkeletonInstaller\OptionalPackagesInstaller;

class OptionalPackagesInstallerTest extends TestCase
{
    public function assertModuleEnabled($module, $package, $appConfig, $composer)
    {
        // This will do two things:
        // - assert that the package was added to composer
        // - assert that the application configuration now references the module
    }

    public function assertModuleNotEnabled($module, $package, $appConfig, $composer)
    {
        // This will do two things:
        // - assert that the package was NOT added to composer
        // - assert that the application configuration DOES NOT reference the module
    }

    public function assertDevelopmentModuleEnabled($module, $package, $appConfig, $composer)
    {
        // This will do two things:
        // - assert that the package was added to composer as a dev requirement
        // - assert that the development configuration now references the module
    }

    public function assertDevelopmentModuleNotEnabled($module, $package, $appConfig, $composer)
    {
        // This will do two things:
        // - assert that the package was NOT added to composer as a dev requirement
        // - assert that the development configuration DOES NOT reference the module
    }

    public function assertPackageInstalled($package, $composer)
    {
        // This will assert that the given package was added as a requirement
        // to composer.
    }

    public function assertDevPackageInstalled($package, $composer)
    {
        // This will assert that the given package was added as a DEVELOPMENT
        // requirement to composer.
    }

    public function setUpPromptExpectations($moduleEnabled, $io)
    {
        // This will setup the $io expectations, indicating a "n" response for
        // all prompts EXCEPT the one for the specific module to enable.
        //
        // It will ALWAYS set an expectation that the "minimal" option was
        // answered as "n".
    }

    public function testDoesNothingIfRootPackageHasNoOptionalDependenciesDefined()
    {
        $this->markTestIncomplete();
    }

    public function testChoosingMinimalInstallSkipsAllOtherPrompts()
    {
        $this->markTestIncomplete();
    }

    public function testAddsNewRequiredPackageToComposerJsonAndTriggersUpdate()
    {
        $this->markTestIncomplete();
    }

    /**
     * @depends testAddsNewRequiredPackageToComposerJsonAndTriggersUpdate
     */
    public function testUpdatesModuleConfigToAddModule()
    {
        $this->markTestIncomplete();
    }

    public function testAddsNewDevRequirementPackageToComposerJsonAndTriggersUpdate()
    {
        $this->markTestIncomplete();
    }

    /**
     * @depends testAddsNewDevRequirementPackageToComposerJsonAndTriggersUpdate
     */
    public function testUpdatesDevelopmentConfigToAddModule()
    {
        $this->markTestIncomplete();
    }
}
