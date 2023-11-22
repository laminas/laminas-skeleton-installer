<?php

declare(strict_types=1);

namespace LaminasTest\SkeletonInstaller;

use Composer\Composer;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\Package\AliasPackage;
use Composer\Package\Locker;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Repository\RepositoryManager;
use Laminas\SkeletonInstaller\Uninstaller;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function array_shift;
use function file_get_contents;
use function json_decode;
use function json_encode;

class UninstallerTest extends TestCase
{
    /**
     * @param ExpectationFailedException[] $constraintFailures Captures constraint failures
     *      to ensure try-catch does not swallow them.
     */
    protected function setUpIo(array &$constraintFailures): IOInterface&MockObject
    {
        $io = $this->createMock(IOInterface::class);

        $matcher = self::exactly(4);
        $io->expects($matcher)
            ->method('write')
            ->willReturnCallback(function (string $message) use ($matcher, &$constraintFailures) {
                try {
                    $this->assertStringNotContainsString('Package not installed; nothing to do', $message);
                    /** @psalm-suppress InternalMethod */
                    $contains = match ($matcher->numberOfInvocations()) {
                        1 => 'Removing laminas/laminas-skeleton-installer',
                        2 => 'Removed plugin laminas/laminas-skeleton-installer',
                        3 => 'Removing from composer.json',
                        4 => 'Complete!',
                    };
                    $this->assertStringContainsString($contains, $message);
                } catch (ExpectationFailedException $e) {
                    $constraintFailures[] = $e;
                    throw $e;
                }
            });

        return $io;
    }

    protected function createLockPackages(
        InstalledRepositoryInterface&MockObject $repository,
        Locker&MockObject $locker
    ): void {
        $required = self::createStub(PackageInterface::class);
        $required->method('getName')
            ->willReturn('some/required');
        $required->method('isDev')
            ->willReturn(false);

        $dev = self::createStub(PackageInterface::class);
        $dev->method('getName')
            ->willReturn('some/dev');
        $dev->method('isDev')
            ->willReturn(true);

        $skeleton = $this->createMock(PackageInterface::class);
        $skeleton->method('getName')
            ->willReturn('laminas/laminas-skeleton-installer');
        $skeleton->expects(self::never())
            ->method('isDev');

        $alias = $this->createStub(AliasPackage::class);
        $alias->method('getName')
            ->willReturn('some/alias');
        $alias->method('isDev')
            ->willReturn(false);

        $repository->method('getPackages')
            ->willReturn([
                $required,
                $dev,
                $skeleton,
                $alias,
            ]);

        $locker->method('getPlatformRequirements')
            ->willReturn([]);
        $locker->method('getMinimumStability')
            ->willReturn('stable');
        $locker->method('getStabilityFlags')
            ->willReturn([]);
        $locker->method('getPreferStable')
            ->willReturn(true);
        $locker->method('getPreferLowest')
            ->willReturn(false);
        $locker->method('getPlatformOverrides')
            ->willReturn([]);

        $locker->expects(self::once())
            ->method('setLockData')
            ->with(
                [$required],
                [$dev],
                [],
                [],
                [$alias],
                'stable',
                [],
                true,
                false,
                []
            )->willReturn(true);
    }

    protected function setUpComposerAndDependencies(): Composer&MockObject
    {
        $composer = $this->createMock(Composer::class);

        $package    = self::createStub(PackageInterface::class);
        $repository = $this->createMock(InstalledRepositoryInterface::class);
        $repository->method('findPackage')
            ->with(Uninstaller::PLUGIN_NAME, '*')
            ->willReturn($package);

        $locker = $this->createMock(Locker::class);
        $this->createLockPackages($repository, $locker);
        $composer->method('getLocker')
            ->willReturn($locker);

        $repoManager = self::createStub(RepositoryManager::class);
        $repoManager->method('getLocalRepository')
            ->willReturn($repository);

        $composer->method('getRepositoryManager')
            ->willReturn($repoManager);

        $installationManager = $this->createMock(InstallationManager::class);
        $installationManager->expects(self::once())
            ->method('uninstall')
            ->with($repository, $this->callback(function ($arg) use ($package) {
                if (! $arg instanceof UninstallOperation) {
                    return false;
                }

                return $package === $arg->getPackage();
            }));

        $composer->method('getInstallationManager')
            ->willReturn($installationManager);

        return $composer;
    }

    protected function setUpComposerJson(Uninstaller $uninstaller): void
    {
        $project = vfsStream::setup('project');
        vfsStream::newFile('composer.json')
            ->at($project)
            ->setContent($this->createComposerJson());

        $r = new ReflectionProperty($uninstaller, 'composerFileFactory');
        $r->setValue($uninstaller, function () {
            return vfsStream::url('project/composer.json');
        });
    }

    protected function createComposerJson(): string
    {
        return json_encode([
            'name'        => 'test/project',
            'type'        => 'project',
            'description' => 'This is a test project',
            'require'     => [
                'laminas/laminas-skeleton-installer' => '^1.0.0-dev@dev',
            ],
        ]);
    }

    public function testRemovesPluginInstallation(): void
    {
        /** @var ExpectationFailedException[] $constraintFailures */
        $constraintFailures = [];

        $io       = $this->setUpIo($constraintFailures);
        $composer = $this->setUpComposerAndDependencies();

        $uninstaller = new Uninstaller($composer, $io);
        $this->setUpComposerJson($uninstaller);

        $uninstaller();

        $composer = json_decode(file_get_contents(vfsStream::url('project/composer.json')), true);
        $this->assertFalse(isset($composer['require']['laminas-skeleton-installer']));

        $escapedFailure = array_shift($constraintFailures);
        if ($escapedFailure) {
            throw $escapedFailure;
        }
    }
}
