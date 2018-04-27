<?php
/**
 * @see       https://github.com/zendframework/zend-skeleton-installer for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-skeleton-installer/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\SkeletonInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use PHPUnit\Framework\TestCase;
use Zend\SkeletonInstaller\Plugin;

class PluginTest extends TestCase
{
    public function testActivateSetsComposerAndIoProperties()
    {
        $composer = $this->prophesize(Composer::class)->reveal();
        $io = $this->prophesize(IOInterface::class)->reveal();

        $plugin = new Plugin();
        $plugin->activate($composer, $io);

        $this->assertAttributeSame($composer, 'composer', $plugin);
        $this->assertAttributeSame($io, 'io', $plugin);
    }

    public function testSubscribesToExpectedEvents()
    {
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
}
