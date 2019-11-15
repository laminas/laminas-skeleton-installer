<?php
/**
 * @see       https://github.com/zendframework/zend-skeleton-installer for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-skeleton-installer/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\SkeletonInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Zend\SkeletonInstaller\BroadcastEventDispatcher;

class BroadcastEventDispatcherTest extends TestCase
{
    /** @var Composer|ObjectProphecy */
    private $composer;

    /** @var IOInterface|ObjectProphecy */
    private $io;

    protected function setUp()
    {
        parent::setUp();

        $this->composer = $this->prophesize(Composer::class);
        $this->io = $this->prophesize(IOInterface::class);
    }

    public function testBroadcastEvent()
    {
        $originEventDispatcher = new TestAsset\OriginEventDispatcher(
            $this->composer->reveal(),
            $this->io->reveal()
        );
        $broadcastEventDispatcher = new BroadcastEventDispatcher(
            $this->composer->reveal(),
            $this->io->reveal(),
            null,
            $originEventDispatcher,
            ['my-custom-event']
        );

        // Hard to mock everything for that test...
        // $broadcastEventDispatcher->dispatch('other-event');
        // self::assertCount(0, $originEventDispatcher->dispatchedEvents);

        $broadcastEventDispatcher->dispatch('my-custom-event');
        self::assertCount(1, $originEventDispatcher->dispatchedEvents);
        self::assertSame('my-custom-event', $originEventDispatcher->dispatchedEvents[0]->getName());
    }
}
