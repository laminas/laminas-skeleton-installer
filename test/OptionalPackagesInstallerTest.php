<?php

declare(strict_types=1);

namespace LaminasTest\SkeletonInstaller;

use Composer\Composer;
use Composer\Installer;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;
use Laminas\SkeletonInstaller\OptionalPackagesInstaller;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophecy\ProphecyInterface;
use ReflectionProperty;

use function array_key_exists;
use function file_get_contents;
use function is_array;
use function json_decode;
use function json_encode;
use function version_compare;

class OptionalPackagesInstallerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @psalm-var ObjectProphecy<Composer>
     * @var Composer|ProphecyInterface
     */
    private $composer;

    /**
     * @psalm-var ObjectProphecy<IOInterface>
     * @var IOInterface|ProphecyInterface
     */
    private $io;

    /** @var OptionalPackagesInstaller */
    private $installer;

    public function setUp(): void
    {
        $this->composer = $this->prophesize(Composer::class);
        $this->io       = $this->prophesize(IOInterface::class);

        $this->installer = new OptionalPackagesInstaller(
            $this->composer->reveal(),
            $this->io->reveal()
        );
    }

    /**
     * @param any $data
     */
    protected function setUpComposerJson($data = null): void
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

    /**
     * @param any $data
     */
    protected function createComposerJson($data): string
    {
        $data = $data ?: $this->getDefaultComposerData();
        return json_encode($data);
    }

    protected function getDefaultComposerData(): array
    {
        return [
            'name'        => 'test/project',
            'type'        => 'project',
            'description' => 'This is a test project',
            'require'     => [
                'laminas/laminas-skeleton-installer' => '^1.0.0-dev@dev',
            ],
        ];
    }

    /**
     * @param array $expectedPackages
     * @param int $expectedReturn
     */
    protected function setUpComposerInstaller(array $expectedPackages, $expectedReturn = 0): void
    {
        $installer = $this->prophesize(Installer::class);
        $installer->setDevMode(true)->shouldBeCalled();
        $installer->setUpdate(true)->shouldBeCalled();

        $this->addExpectedPackagesAssertionBasedOnComposerVersion($installer, $expectedPackages);
        $installer->run()->willReturn($expectedReturn);

        $r = new ReflectionProperty($this->installer, 'installerFactory');
        $r->setAccessible(true);
        $r->setValue($this->installer, function () use ($installer) {
            return $installer->reveal();
        });
    }

    public function testDoesNothingIfRootPackageHasNoOptionalDependenciesDefined(): void
    {
        $package = $this->prophesize(RootPackageInterface::class);
        $package->getExtra()->willReturn([]);
        $this->composer->getPackage()->willReturn($package->reveal());

        $this->io->write(Argument::any())->shouldNotBeCalled();

        $installer = $this->installer;
        $this->assertNull($installer());
    }

    public function testChoosingMinimalInstallSkipsInstallation(): void
    {
        $package = $this->prophesize(RootPackageInterface::class);
        $package->getExtra()->willReturn([
            'laminas-skeleton-installer' => [
                [
                    'name'       => 'laminas/laminas-db',
                    'constraint' => '^2.5',
                    'prompt'     => 'This is a prompt',
                    'module'     => true,
                ],
            ],
        ]);
        $this->composer->getPackage()->willReturn($package->reveal());

        $this->io->ask(Argument::containingString('Do you want a minimal install'), 'y')
            ->shouldBeCalled()
            ->willReturn('y');
        $this->io->write(Argument::containingString('Removing optional packages from composer.json'))->shouldBeCalled();
        $this->io->write(Argument::containingString('Updating composer.json'))->shouldBeCalled();

        $this->setUpComposerJson();

        $installer = $this->installer;
        $this->assertNull($installer());

        $json     = file_get_contents(vfsStream::url('project/composer.json'));
        $composer = json_decode($json, true);
        $this->assertFalse(isset($composer['extra']['laminas-skeleton-installer']));
    }

    public function testChoosingNoOptionalPackagesDuringPromptsSkipsInstallation(): void
    {
        $package = $this->prophesize(RootPackageInterface::class);
        $package->getExtra()->willReturn([
            'laminas-skeleton-installer' => [
                [
                    'name'       => 'laminas/laminas-db',
                    'constraint' => '^2.5',
                    'prompt'     => 'This is a prompt',
                    'module'     => true,
                ],
            ],
        ]);
        $this->composer->getPackage()->willReturn($package->reveal());

        $this->io->ask(Argument::containingString('Do you want a minimal install'), 'y')
            ->shouldBeCalled()
            ->willReturn('n');

        $this->io->ask(
            Argument::allOf(Argument::containingString('This is a prompt'), Argument::containingString('y/N')),
            Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn('n');

        $this->io->write(Argument::containingString('No optional packages selected'))->shouldBeCalled();
        $this->io->write(Argument::containingString('Removing optional packages from composer.json'))->shouldBeCalled();
        $this->io->write(Argument::containingString('Updating composer.json'))->shouldBeCalled();

        $this->setUpComposerJson();

        $installer = $this->installer;
        $this->assertNull($installer());

        $json     = file_get_contents(vfsStream::url('project/composer.json'));
        $composer = json_decode($json, true);
        $this->assertFalse(isset($composer['extra']['laminas-skeleton-installer']));
    }

    public function testInstallerFailureShouldLeaveOptionalPackagesIntact()
    {
        $package = $this->prophesize(RootPackageInterface::class);
        $package->getExtra()->willReturn([
            'laminas-skeleton-installer' => [
                [
                    'name'       => 'laminas/laminas-db',
                    'constraint' => '^2.5',
                    'prompt'     => 'This is a prompt',
                    'module'     => true,
                ],
            ],
        ]);
        $this->composer->getPackage()->willReturn($package->reveal());

        $this->io->ask(Argument::containingString('Do you want a minimal install'), 'y')
            ->shouldBeCalled()
            ->willReturn('n');

        $this->io->ask(
            Argument::allOf(Argument::containingString('This is a prompt'), Argument::containingString('y/N')),
            Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn('y');

        $this->io->write(Argument::containingString('Will install laminas/laminas-db'))->shouldBeCalled();
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

            if (! array_key_exists('laminas/laminas-db', $arg)) {
                return false;
            }

            if (! $arg['laminas/laminas-db'] instanceof Link) {
                return false;
            }

            return true;
        }))->shouldBeCalled();

        $this->setUpComposerInstaller(['laminas/laminas-db'], 1);

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
            'laminas-skeleton-installer' => [
                [
                    'name'       => 'laminas/laminas-db',
                    'constraint' => '^2.5',
                    'prompt'     => 'This is a prompt',
                    'module'     => true,
                ],
            ],
        ]);
        $this->composer->getPackage()->willReturn($package->reveal());

        $this->io->ask(Argument::containingString('Do you want a minimal install'), 'y')
            ->shouldBeCalled()
            ->willReturn('n');

        $this->io->ask(
            Argument::allOf(Argument::containingString('This is a prompt'), Argument::containingString('y/N')),
            Argument::any()
        )
            ->shouldBeCalled()
            ->willReturn('y');

        $this->io->write(Argument::containingString('Will install laminas/laminas-db'))->shouldBeCalled();
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

            if (! array_key_exists('laminas/laminas-db', $arg)) {
                return false;
            }

            if (! $arg['laminas/laminas-db'] instanceof Link) {
                return false;
            }

            return true;
        }))->shouldBeCalled();

        $this->setUpComposerInstaller(['laminas/laminas-db']);

        $this->io
            ->write(Argument::containingString('Running an update to install optional packages'))
            ->shouldBeCalled();

        $this->setUpComposerJson();
        $this->io->write(Argument::containingString('Updating composer.json'))->shouldBeCalled();

        $installer = $this->installer;
        $this->assertNull($installer());
    }

    /**
     * @param ObjectProphecy<Installer> $installer
     */
    private function addExpectedPackagesAssertionBasedOnComposerVersion(
        ObjectProphecy $installer,
        array $expectedPackages
    ) {
        // Composer v1.0 support
        if (version_compare(PluginInterface::PLUGIN_API_VERSION, '2.0', 'lt')) {
            $installer->setUpdateWhitelist($expectedPackages)->shouldBeCalled();

            return;
        }

        $installer->setUpdateAllowList($expectedPackages)->shouldBeCalled();
    }
}
