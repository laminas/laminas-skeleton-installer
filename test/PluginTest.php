<?php

declare(strict_types=1);

namespace LaminasTest\SkeletonInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use Laminas\SkeletonInstaller\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class PluginTest extends TestCase
{
    public function testActivateSetsComposerAndIoProperties(): void
    {
        $composer = self::createStub(Composer::class);
        $io       = self::createStub(IOInterface::class);

        $plugin = new Plugin();
        $plugin->activate($composer, $io);

        $rComposer = new ReflectionProperty($plugin, 'composer');

        $this->assertSame($composer, $rComposer->getValue($plugin));

        $rIo = new ReflectionProperty($plugin, 'io');

        $this->assertSame($io, $rIo->getValue($plugin));
    }

    public function testSubscribesToExpectedEventsForComposer2()
    {
        $subscribers = Plugin::getSubscribedEvents();
        $this->assertArrayHasKey('post-install-cmd', $subscribers);
        $this->assertArrayHasKey('post-update-cmd', $subscribers);

        $expected = [
            ['installOptionalDependencies', 1000],
        ];

        $this->assertEquals($expected, $subscribers['post-install-cmd']);
        $this->assertEquals($expected, $subscribers['post-update-cmd']);
    }
}
