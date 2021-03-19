<?php

/**
 * @see       https://github.com/laminas/laminas-skeleton-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-skeleton-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-skeleton-installer/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\SkeletonInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Laminas\SkeletonInstaller\Plugin;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use ReflectionProperty;
use function version_compare;

class PluginTest extends TestCase
{
    use ProphecyTrait;

    public function testActivateSetsComposerAndIoProperties()
    {
        $composer = $this->prophesize(Composer::class)->reveal();
        $io = $this->prophesize(IOInterface::class)->reveal();

        $plugin = new Plugin();
        $plugin->activate($composer, $io);

        $rComposer = new ReflectionProperty($plugin, 'composer');
        $rComposer->setAccessible(true);

        $this->assertSame($composer, $rComposer->getValue($plugin));

        
        $rIo = new ReflectionProperty($plugin, 'io');
        $rIo->setAccessible(true);

        $this->assertSame($io, $rIo->getValue($plugin));
    }

    public function testSubscribesToExpectedEventsForComposer1()
    {
        if (! version_compare(PluginInterface::PLUGIN_API_VERSION, '2.0', 'lt')) {
            $this->markTestSkipped('This test is only needed for composer v1.');

            return;
        }

        $subscribers = Plugin::getSubscribedEvents();
        $this->assertArrayHasKey('post-install-cmd', $subscribers);
        $this->assertArrayHasKey('post-update-cmd', $subscribers);

        $expected = [
            ['installOptionalDependencies', 1000],
            ['uninstallPlugin'],
        ];

        $this->assertEquals($expected, $subscribers['post-install-cmd']);
        $this->assertEquals($expected, $subscribers['post-update-cmd']);
    }

    public function testSubscribesToExpectedEventsForComposer2()
    {
        if (! version_compare(PluginInterface::PLUGIN_API_VERSION, '2.0.0', 'ge')) {
            $this->markTestSkipped('This test is only needed for composer v2.');

            return;
        }

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
