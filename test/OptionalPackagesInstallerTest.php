<?php

declare(strict_types=1);

namespace LaminasTest\SkeletonInstaller;

use Composer\Composer;
use Composer\Installer;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Package\RootPackageInterface;
use Laminas\SkeletonInstaller\OptionalPackagesInstaller;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function array_key_exists;
use function array_shift;
use function file_get_contents;
use function json_decode;
use function json_encode;

class OptionalPackagesInstallerTest extends TestCase
{
    /** @var Composer&MockObject */
    private $composer;

    /** @var IOInterface&MockObject */
    private $io;

    /** @var OptionalPackagesInstaller */
    private $installer;

    public function setUp(): void
    {
        $this->composer = $this->createMock(Composer::class);
        $this->io       = $this->createMock(IOInterface::class);

        $this->installer = new OptionalPackagesInstaller(
            $this->composer,
            $this->io,
        );
    }

    protected function setUpComposerJson(?array $data = null): void
    {
        $project = vfsStream::setup('project');
        vfsStream::newFile('composer.json')
            ->at($project)
            ->setContent($this->createComposerJson($data));

        $r = new ReflectionProperty($this->installer, 'composerFileFactory');
        $r->setValue($this->installer, function () {
            return vfsStream::url('project/composer.json');
        });
    }

    protected function createComposerJson(?array $data): string
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

    protected function setUpComposerInstaller(array $expectedPackages, int $expectedReturn = 0): void
    {
        $installer = $this->createMock(Installer::class);
        $installer->expects(self::once())
            ->method('setDevMode')
            ->with(true);

        $installer->expects(self::once())
            ->method('setUpdate')
            ->with(true);

        $installer->expects(self::once())
            ->method('setUpdateAllowList')
            ->with($this->equalToCanonicalizing($expectedPackages));
        $installer->method('run')
            ->willReturn($expectedReturn);

        $r = new ReflectionProperty($this->installer, 'installerFactory');
        $r->setValue($this->installer, static function () use ($installer) {
            return $installer;
        });
    }

    public function testDoesNothingIfRootPackageHasNoOptionalDependenciesDefined(): void
    {
        $package = self::createStub(RootPackageInterface::class);
        $package->method('getExtra')
            ->willReturn([]);

        $this->composer->method('getPackage')
            ->willReturn($package);

        $this->io->expects(self::never())
            ->method('write');

        $installer = $this->installer;
        $this->assertNull($installer());
    }

    public function testChoosingMinimalInstallSkipsInstallation(): void
    {
        /** @var ExpectationFailedException[] $constraintFailures */
        $constraintFailures = [];

        $package = self::createStub(RootPackageInterface::class);
        $package->method('getExtra')
            ->willReturn([
                'laminas-skeleton-installer' => [
                    [
                        'name'       => 'laminas/laminas-db',
                        'constraint' => '^2.5',
                        'prompt'     => 'This is a prompt',
                        'module'     => true,
                    ],
                ],
            ]);

        $this->composer->method('getPackage')
            ->willReturn($package);

        $this->io->expects(self::once())
            ->method('ask')
            ->with($this->stringContains('Do you want a minimal install'), 'y')
            ->willReturn('y');

        $matcher = self::exactly(2);
        $this->io->expects($matcher)
            ->method('write')
            ->willReturnCallback(function (string $message) use ($matcher, &$constraintFailures) {
                try {
                    /** @psalm-suppress InternalMethod */
                    $contains = match ($matcher->numberOfInvocations()) {
                        1 => 'Removing optional packages from composer.json',
                        2 => 'Updating composer.json',
                    };
                    $this->assertStringContainsString($contains, $message);
                } catch (ExpectationFailedException $e) {
                    $constraintFailures[] = $e;
                    throw $e;
                }
            });

        $this->setUpComposerJson();

        $installer = $this->installer;
        $this->assertNull($installer());

        $json     = file_get_contents(vfsStream::url('project/composer.json'));
        $composer = json_decode($json, true);
        $this->assertFalse(isset($composer['extra']['laminas-skeleton-installer']));

        $escapedFailure = array_shift($constraintFailures);
        if ($escapedFailure) {
            throw $escapedFailure;
        }
    }

    public function testChoosingNoOptionalPackagesDuringPromptsSkipsInstallation(): void
    {
        /** @var ExpectationFailedException[] $constraintFailures */
        $constraintFailures = [];

        $package = self::createStub(RootPackageInterface::class);
        $package->method('getExtra')
            ->willReturn([
                'laminas-skeleton-installer' => [
                    [
                        'name'       => 'laminas/laminas-db',
                        'constraint' => '^2.5',
                        'prompt'     => 'This is a prompt',
                        'module'     => true,
                    ],
                ],
            ]);
        $this->composer->method('getPackage')
            ->willReturn($package);

        $matcher = self::exactly(2);
        $this->io->expects($matcher)
            ->method('ask')
            ->willReturnCallback(function (string $question, mixed $default) use ($matcher, &$constraintFailures) {
                try {
                    /** @psalm-suppress InternalMethod */
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertStringContainsString(
                                'Do you want a minimal install',
                                $question
                            );
                            $this->assertSame('y', $default);

                            return 'n';
                        case 2:
                            $this->assertStringContainsString(
                                'This is a prompt',
                                $question
                            );
                            $this->assertSame('n', $default);

                            return 'n';
                        default:
                            $this->fail('Exceeded number of invocations');
                    }
                } catch (ExpectationFailedException $e) {
                    $constraintFailures[] = $e;
                    throw $e;
                }
            });

        $matcher = self::exactly(3);
        $this->io->expects($matcher)
            ->method('write')
            ->willReturnCallback(function (string $message) use ($matcher, &$constraintFailures) {
                try {
                    /** @psalm-suppress InternalMethod */
                    $contains = match ($matcher->numberOfInvocations()) {
                        1 => 'No optional packages selected',
                        2 => 'Removing optional packages from composer.json',
                        3 => 'Updating composer.json',
                    };
                    $this->assertStringContainsString($contains, $message);
                } catch (ExpectationFailedException $e) {
                    $constraintFailures[] = $e;
                    throw $e;
                }
            });

        $this->setUpComposerJson();

        $installer = $this->installer;
        $this->assertNull($installer());

        $json     = file_get_contents(vfsStream::url('project/composer.json'));
        $composer = json_decode($json, true);
        $this->assertFalse(isset($composer['extra']['laminas-skeleton-installer']));

        $escapedFailure = array_shift($constraintFailures);
        if ($escapedFailure) {
            throw $escapedFailure;
        }
    }

    public function testInstallerFailureShouldLeaveOptionalPackagesIntact(): void
    {
        /** @var ExpectationFailedException[] $constraintFailures */
        $constraintFailures = [];

        $package = self::createStub(RootPackageInterface::class);
        $package->method('getExtra')
            ->willReturn([
                'laminas-skeleton-installer' => [
                    [
                        'name'       => 'laminas/laminas-db',
                        'constraint' => '^2.5',
                        'prompt'     => 'This is a prompt',
                        'module'     => true,
                    ],
                ],
            ]);
        $package->method('getRequires')
            ->willReturn([]);
        $package->method('setRequires')
            ->willReturnCallback(function (array $arg) {
                if (! array_key_exists('laminas/laminas-db', $arg)) {
                    return false;
                }

                if (! $arg['laminas/laminas-db'] instanceof Link) {
                    return false;
                }

                return true;
            });
        $this->composer->method('getPackage')
            ->willReturn($package);

        $matcher = self::exactly(2);
        $this->io->expects($matcher)
            ->method('ask')
            ->willReturnCallback(function (string $question, mixed $default) use ($matcher, &$constraintFailures) {
                try {
                    /** @psalm-suppress InternalMethod */
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertStringContainsString(
                                'Do you want a minimal install',
                                $question
                            );
                            $this->assertSame('y', $default);

                            return 'n';
                        case 2:
                            $this->assertStringContainsString(
                                'This is a prompt',
                                $question
                            );
                            $this->assertSame('n', $default);

                            return 'y';
                        default:
                            $this->fail('Exceeded number of invocations');
                    }
                } catch (ExpectationFailedException $e) {
                    $constraintFailures[] = $e;
                    throw $e;
                }
            });

        $matcher = self::exactly(5);
        $this->io->expects($matcher)
            ->method('write')
            ->willReturnCallback(function (string $message) use ($matcher, &$constraintFailures) {
                try {
                    $this->assertStringNotContainsString(
                        'No optional packages selected',
                        $message
                    );

                    /** @psalm-suppress InternalMethod */
                    $contains = match ($matcher->numberOfInvocations()) {
                        1 => 'Will install laminas/laminas-db',
                        // phpcs:ignore  Generic.Files.LineLength.TooLong
                        2 => 'When prompted to install as a module, select application.config.php or modules.config.php',
                        3 => 'Updating root package',
                        4 => 'Running an update to install optional packages',
                        5 => 'Error installing optional packages',
                    };
                    $this->assertStringContainsString($contains, $message);
                } catch (ExpectationFailedException $e) {
                    $constraintFailures[] = $e;
                    throw $e;
                }
            });

        $this->setUpComposerInstaller(['laminas/laminas-db'], 1);

        $installer = $this->installer;
        $this->assertNull($installer());

        $escapedFailure = array_shift($constraintFailures);
        if ($escapedFailure) {
            throw $escapedFailure;
        }
    }

    public function testAddingAnOptionalPackageAddsItToComposerAndUpdatesAppConfig(): void
    {
        /** @var ExpectationFailedException[] $constraintFailures */
        $constraintFailures = [];

        $package = self::createStub(RootPackageInterface::class);
        $package->method('getExtra')
            ->willReturn([
                'laminas-skeleton-installer' => [
                    [
                        'name'       => 'laminas/laminas-db',
                        'constraint' => '^2.5',
                        'prompt'     => 'This is a prompt',
                        'module'     => true,
                    ],
                ],
            ]);
        $package->method('getRequires')
            ->willReturn([]);
        $package->method('setRequires')
            ->willReturnCallback(function (array $arg) {
                if (! array_key_exists('laminas/laminas-db', $arg)) {
                    return false;
                }

                if (! $arg['laminas/laminas-db'] instanceof Link) {
                    return false;
                }

                return true;
            });
        $this->composer->method('getPackage')
            ->willReturn($package);

        $matcher = self::exactly(2);
        $this->io->expects($matcher)
            ->method('ask')
            ->willReturnCallback(function (string $question, mixed $default) use ($matcher, &$constraintFailures) {
                try {
                    /** @psalm-suppress InternalMethod */
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertStringContainsString(
                                'Do you want a minimal install',
                                $question
                            );
                            $this->assertSame('y', $default);

                            return 'n';
                        case 2:
                            $this->assertStringContainsString(
                                'This is a prompt',
                                $question
                            );
                            $this->assertSame('n', $default);

                            return 'y';
                        default:
                            $this->fail('Exceeded number of invocations');
                    }
                } catch (ExpectationFailedException $e) {
                    $constraintFailures[] = $e;
                    throw $e;
                }
            });

        $matcher = self::exactly(5);
        $this->io->expects($matcher)
            ->method('write')
            ->willReturnCallback(function (string $message) use ($matcher, &$constraintFailures) {
                try {
                    $this->assertStringNotContainsString(
                        'No optional packages selected',
                        $message
                    );

                    /** @psalm-suppress InternalMethod */
                    $contains = match ($matcher->numberOfInvocations()) {
                        1 => 'Will install laminas/laminas-db',
                        // phpcs:ignore  Generic.Files.LineLength.TooLong
                        2 => 'When prompted to install as a module, select application.config.php or modules.config.php',
                        3 => 'Updating root package',
                        4 => 'Running an update to install optional packages',
                        5 => 'Updating composer.json',
                    };
                    $this->assertStringContainsString($contains, $message);
                } catch (ExpectationFailedException $e) {
                    $constraintFailures[] = $e;
                    throw $e;
                }
            });

        $this->setUpComposerInstaller(['laminas/laminas-db'], 0);
        $this->setUpComposerJson();

        $installer = $this->installer;
        $this->assertNull($installer());

        $escapedFailure = array_shift($constraintFailures);
        if ($escapedFailure) {
            throw $escapedFailure;
        }
    }
}
