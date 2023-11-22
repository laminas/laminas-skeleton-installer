<?php

declare(strict_types=1);

namespace LaminasTest\SkeletonInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use Laminas\SkeletonInstaller\BroadcastEventDispatcher;
use PHPUnit\Framework\TestCase;

class BroadcastEventDispatcherTest extends TestCase
{
    public function testBroadcastEvent(): void
    {
        $composer = self::createStub(Composer::class);
        $io       = self::createStub(IOInterface::class);

        $originEventDispatcher    = new TestAsset\OriginEventDispatcher(
            $composer,
            $io,
        );
        $broadcastEventDispatcher = new BroadcastEventDispatcher(
            $composer,
            $io,
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
