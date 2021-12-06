<?php

declare(strict_types=1);

namespace LaminasTest\SkeletonInstaller\TestAsset;

use Composer\EventDispatcher\Event;
use Composer\EventDispatcher\EventDispatcher;

class OriginEventDispatcher extends EventDispatcher
{
    /** @var Event[] */
    public $dispatchedEvents = [];

    protected function doDispatch(Event $event): int
    {
        $this->dispatchedEvents[] = $event;

        return 1;
    }
}
