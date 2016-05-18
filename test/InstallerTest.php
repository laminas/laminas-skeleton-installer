<?php
/**
 * @link      http://github.com/zendframework/zend-skeleton-installer for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\SkeletonInstaller;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\SkeletonInstaller\Installer;

class InstallerTest extends TestCase
{
    public function assertModuleEnabled($module, $package, $appConfig, $composer)
    {
        // This will do two things:
        // - assert that the package was added to composer
        // - assert that the application configuration now references the module
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

    public function testChoosingMinimalInstallSkipsAllOtherPrompts()
    {
        $this->markTestIncomplete();
    }

    public function validInstallationOptions()
    {
        return [
            'minimal' => [[]],
            'partial' => [[]],
            'full'    => [[]],
        ];
    }

    /**
     * @dataProvider validInstallationOptions
     */
    public function testAllInstallationsCreateDataDirectoryWithCorrectPermissions(array $promptAnswers)
    {
        $this->markTestIncomplete();
    }

    /**
     * @dataProvider validInstallationOptions
     */
    public function testAllInstallationsRemoveComposerAsDevDependency(array $promptAnswers)
    {
        $this->markTestIncomplete();
    }

    /**
     * @dataProvider validInstallationOptions
     */
    public function testAllInstallationsLeavePhpunitAsDevDependency(array $promptAnswers)
    {
        $this->markTestIncomplete();
    }

    public function testChoosingCachingEnablesCacheModule()
    {
        $this->markTestIncomplete();
    }

    public function testChoosingConsoleEnablesMvcConsoleModule()
    {
        $this->markTestIncomplete();
    }

    public function testChoosingDatabaseEnablesDbModule()
    {
        $this->markTestIncomplete();
    }

    public function testChoosingFormsEnablesFormHydratorInputFilterValidatorAndFilterModules()
    {
        $this->markTestIncomplete();
    }

    public function testChoosingI18nEnablesMvcI18nAndI18nModules()
    {
        $this->markTestIncomplete();
    }

    public function testChoosingJsonInstallsJsonPackage()
    {
        $this->markTestIncomplete();
    }

    public function testChoosingLoggingEnablesLogModule()
    {
        $this->markTestIncomplete();
    }

    public function testChoosingPsr7InstallsPsr7bridgePackage()
    {
        $this->markTestIncomplete();
    }

    public function testChoosingSessionEnablesSessionModule()
    {
        $this->markTestIncomplete();
    }

    public function testChoosingTestingInstallsTestPackageAsDevDependency()
    {
        $this->markTestIncomplete();
    }

    public function testChoosingDeploymentInstallsZfDeployAsDevDependency()
    {
        $this->markTestIncomplete();
    }

    public function testChoosingPluginsEnablesAllPluginModules()
    {
        $this->markTestIncomplete();
    }

    public function testChoosingDiIntegrationEnablesServiceManagerDiModule()
    {
        $this->markTestIncomplete();
    }
}
