<?php

/**
 * @see       https://github.com/laminas/laminas-skeleton-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-skeleton-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-skeleton-installer/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\SkeletonInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use Laminas\SkeletonInstaller\BroadcastEventDispatcher;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class BroadcastEventDispatcherTest extends TestCase
{
    use ProphecyTrait;

    /** @var Composer|ObjectProphecy */
    private $composer;

    /** @var IOInterface|ObjectProphecy */
    private $io;

    protected function setUp(): void
    {
        parent::setUp();

        $this->composer = $this->prophesize(Composer::class);
        $this->io       = $this->prophesize(IOInterface::class);
    }

    public function testBroadcastEvent()
    {
        $originEventDispatcher    = new TestAsset\OriginEventDispatcher(
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
