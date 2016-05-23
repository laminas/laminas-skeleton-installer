<?php
/**
 * @link      http://github.com/zendframework/zend-skeleton-installer for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\SkeletonInstaller;

use Composer\Composer;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\Request;
use Composer\Installer;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\CompositeRepository;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\RepositoryManager;
use Composer\Script\PackageEvent;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use ReflectionProperty;
use Zend\SkeletonInstaller\OptionalPackagesInstaller;
use Zend\ComponentInstaller\ComponentInstaller;

class OptionalPackagesInstallerTest extends TestCase
{
    public function setUp()
    {
        $this->composer = $this->prophesize(Composer::class);
        $this->io = $this->prophesize(IOInterface::class);

        $this->installer = new OptionalPackagesInstaller(
            $this->composer->reveal(),
            $this->io->reveal()
        );
    }

    protected function setUpComposerJson($data = null)
    {
        $project = vfsStream::setup('project');
        vfsStream::newFile('composer.json')
            ->at($project)
            ->setContent($this->createComposerJson($data));

        $r = new ReflectionProperty($this->installer, 'composerFileFactory');
        $r->setAccessible(true);
        $r->setValue($this->installer, function () {
            return vfsStream::url('project/composer.json');
        });
    }

    protected function createComposerJson($data)
    {
        $data = $data ?: $this->getDefaultComposerData();
        return json_encode($data);
    }

    protected function getDefaultComposerData()
    {
        return [
            'name' => 'test/project',
            'type' => 'project',
            'description' => 'This is a test project',
            'require' => [
                'zendframework/zend-skeleton-installer' => '^1.0.0-dev@dev',
            ],
        ];
    }

    protected function setUpComposerInstaller(array $expectedPackages, $expectedReturn = 0)
    {
        $installer = $this->prophesize(Installer::class);
        $installer->disablePlugins()->shouldBeCalled();
        $installer->setUpdate()->shouldBeCalled();
        $installer->setUpdateWhitelist($expectedPackages)->shouldBeCalled();
        $installer->run()->willReturn($expectedReturn);

        $r = new ReflectionProperty($this->installer, 'installerFactory');
        $r->setAccessible(true);
        $r->setValue($this->installer, function () use ($installer) {
            return $installer->reveal();
        });
    }

    protected function setUpApplicationConfig(array $packages)
    {
        $componentInstaller = $this->prophesize(ComponentInstaller::class);
        $componentInstaller->activate($this->composer->reveal(), $this->io->reveal())->shouldBeCalled();
        $localRepository = $this->prophesize(RepositoryInterface::class);

        foreach ($packages as $name => $constraint) {
            $package = $this->prophesize(PackageInterface::class)->reveal();
            $localRepository->findPackage($name, $constraint)->willReturn($package);
            $componentInstaller->onPostPackageInstall(Argument::that(function ($arg) use ($package) {
                if (! $arg instanceof PackageEvent) {
                    return false;
                }

                $operation = $arg->getOperation();
                if (! $operation instanceof InstallOperation) {
                    return false;
                }

                return $package === $operation->getPackage();
            }))->shouldBeCalled();
        }

        $repoManager = $this->prophesize(RepositoryManager::class);
        $repoManager->getLocalRepository()->willReturn($localRepository->reveal());
        $this->composer->getRepositoryManager()->willReturn($repoManager->reveal());

        $r = new ReflectionProperty($this->installer, 'componentInstallerFactory');
        $r->setAccessible(true);
        $r->setValue($this->installer, function () use ($componentInstaller) {
            return $componentInstaller->reveal();
        });
    }

    public function testDoesNothingIfRootPackageHasNoOptionalDependenciesDefined()
    {
        $package = $this->prophesize(RootPackageInterface::class);
        $package->getExtra()->willReturn([]);
        $this->composer->getPackage()->willReturn($package->reveal());

        $this->io->write(Argument::any())->shouldNotBeCalled();

        $installer = $this->installer;
        $this->assertNull($installer());
    }

    public function testChoosingMinimalInstallSkipsInstallation()
    {
        $package = $this->prophesize(RootPackageInterface::class);
        $package->getExtra()->willReturn([
            'zend-skeleton-installer' => [
                [
                    'name'       => 'zendframework/zend-db',
                    'constraint' => '^2.5',
                    'prompt'     => 'This is a prompt',
                    'module'     => true,
                ],
            ],
        ]);
        $this->composer->getPackage()->willReturn($package->reveal());

        $this->io->ask(Argument::that(function ($arg) {
            if (! is_array($arg)) {
                return false;
            }
            $prompt = array_shift($arg);
            return (false !== strpos($prompt, 'Do you want a minimal install'));
        }), 'y')->willReturn('y');
        $this->io->write(Argument::containingString('Removing optional packages from composer.json'))->shouldBeCalled();
        $this->io->write(Argument::containingString('Updating composer.json'))->shouldBeCalled();

        $this->setUpComposerJson();

        $installer = $this->installer;
        $this->assertNull($installer());

        $json = file_get_contents(vfsStream::url('project/composer.json'));
        $composer = json_decode($json, true);
        $this->assertFalse(isset($composer['extra']['zend-skeleton-installer']));
    }

    public function testChoosingNoOptionalPackagesDuringPromptsSkipsInstallation()
    {
        $package = $this->prophesize(RootPackageInterface::class);
        $package->getExtra()->willReturn([
            'zend-skeleton-installer' => [
                [
                    'name'       => 'zendframework/zend-db',
                    'constraint' => '^2.5',
                    'prompt'     => 'This is a prompt',
                    'module'     => true,
                ],
            ],
        ]);
        $this->composer->getPackage()->willReturn($package->reveal());

        $this->io->ask(Argument::that(function ($arg) {
            if (! is_array($arg)) {
                return false;
            }
            $prompt = array_shift($arg);
            return (false !== strpos($prompt, 'Do you want a minimal install'));
        }), 'y')->willReturn('n');

        $this->io->ask(Argument::that(function ($arg) {
            if (! is_array($arg)) {
                return false;
            }
            $prompt = array_shift($arg);
            return (false !== strpos($prompt, 'This is a prompt'))
                && (false !== strpos($prompt, 'y/N'));
        }), 'n')->willReturn('n');

        $this->io->write(Argument::containingString('No optional packages selected'))->shouldBeCalled();
        $this->io->write(Argument::containingString('Removing optional packages from composer.json'))->shouldBeCalled();
        $this->io->write(Argument::containingString('Updating composer.json'))->shouldBeCalled();

        $this->setUpComposerJson();

        $installer = $this->installer;
        $this->assertNull($installer());

        $json = file_get_contents(vfsStream::url('project/composer.json'));
        $composer = json_decode($json, true);
        $this->assertFalse(isset($composer['extra']['zend-skeleton-installer']));
    }

    public function testInstallerFailureShouldLeaveOptionalPackagesIntact()
    {
        $package = $this->prophesize(RootPackageInterface::class);
        $package->getExtra()->willReturn([
            'zend-skeleton-installer' => [
                [
                    'name'       => 'zendframework/zend-db',
                    'constraint' => '^2.5',
                    'prompt'     => 'This is a prompt',
                    'module'     => true,
                ],
            ],
        ]);
        $this->composer->getPackage()->willReturn($package->reveal());

        $this->io->ask(Argument::that(function ($arg) {
            if (! is_array($arg)) {
                return false;
            }
            $prompt = array_shift($arg);
            return (false !== strpos($prompt, 'Do you want a minimal install'));
        }), 'y')->willReturn('n');

        $this->io->ask(Argument::that(function ($arg) {
            if (! is_array($arg)) {
                return false;
            }
            $prompt = array_shift($arg);
            return (false !== strpos($prompt, 'This is a prompt'))
                && (false !== strpos($prompt, 'y/N'));
        }), 'n')->willReturn('y');

        $this->io->write(Argument::containingString('Will install zendframework/zend-db'))->shouldBeCalled();
        $this->io->write(Argument::containingString(
            'When prompted to install as a module, select application.config.php or modules.config.php'
        ))->shouldBeCalled();
        $this->io->write(Argument::containingString('No optional packages selected'))->shouldNotBeCalled();

        $this->io->write(Argument::containingString('Updating root package'))->shouldBeCalled();
        $package->getRequires()->willReturn([]);
        $package->setRequires(Argument::that(function ($arg) {
            if (! is_array($arg)) {
                return false;
            }

            if (! array_key_exists('zendframework/zend-db', $arg)) {
                return false;
            }

            if (! $arg['zendframework/zend-db'] instanceof Link) {
                return false;
            }

            return true;
        }))->shouldBeCalled();

        $this->setUpComposerInstaller(['zendframework/zend-db'], 1);

        $this->io
            ->write(Argument::containingString('Running an update to install optional packages'))
            ->shouldBeCalled();

        $this->io->write(Argument::containingString('Error installing optional packages'))->shouldBeCalled();

        $installer = $this->installer;
        $this->assertNull($installer());
    }

    public function testAddingAnOptionalPackageAddsItToComposerAndUpdatesAppConfig()
    {
        $package = $this->prophesize(RootPackageInterface::class);
        $package->getExtra()->willReturn([
            'zend-skeleton-installer' => [
                [
                    'name'       => 'zendframework/zend-db',
                    'constraint' => '^2.5',
                    'prompt'     => 'This is a prompt',
                    'module'     => true,
                ],
            ],
        ]);
        $this->composer->getPackage()->willReturn($package->reveal());

        $this->io->ask(Argument::that(function ($arg) {
            if (! is_array($arg)) {
                return false;
            }
            $prompt = array_shift($arg);
            return (false !== strpos($prompt, 'Do you want a minimal install'));
        }), 'y')->willReturn('n');

        $this->io->ask(Argument::that(function ($arg) {
            if (! is_array($arg)) {
                return false;
            }
            $prompt = array_shift($arg);
            return (false !== strpos($prompt, 'This is a prompt'))
                && (false !== strpos($prompt, 'y/N'));
        }), 'n')->willReturn('y');

        $this->io->write(Argument::containingString('Will install zendframework/zend-db'))->shouldBeCalled();
        $this->io->write(Argument::containingString(
            'When prompted to install as a module, select application.config.php or modules.config.php'
        ))->shouldBeCalled();
        $this->io->write(Argument::containingString('No optional packages selected'))->shouldNotBeCalled();

        $this->io->write(Argument::containingString('Updating root package'))->shouldBeCalled();
        $package->getRequires()->willReturn([]);
        $package->setRequires(Argument::that(function ($arg) {
            if (! is_array($arg)) {
                return false;
            }

            if (! array_key_exists('zendframework/zend-db', $arg)) {
                return false;
            }

            if (! $arg['zendframework/zend-db'] instanceof Link) {
                return false;
            }

            return true;
        }))->shouldBeCalled();

        $this->setUpComposerInstaller(['zendframework/zend-db']);

        $this->io
            ->write(Argument::containingString('Running an update to install optional packages'))
            ->shouldBeCalled();

        $this->setUpComposerJson();
        $this->io->write(Argument::containingString('Updating composer.json'))->shouldBeCalled();

        $this->setUpApplicationConfig(['zendframework/zend-db' => '^2.5']);
        $this->io->write(Argument::containingString('Updating application configuration'))->shouldBeCalled();

        $installer = $this->installer;
        $this->assertNull($installer());
    }
}
