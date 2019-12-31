<?php

/**
 * @see       https://github.com/laminas/laminas-skeleton-installer for the canonical source repository
 * @copyright https://github.com/laminas/laminas-skeleton-installer/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-skeleton-installer/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\SkeletonInstaller\TestAsset;

use Composer\EventDispatcher\Event;
use Composer\EventDispatcher\EventDispatcher;

class OriginEventDispatcher extends EventDispatcher
{
    /** @var Event[] */
    public $dispatchedEvents = [];

    protected function doDispatch(Event $event)
    {
        $this->dispatchedEvents[] = $event;

        return 1;
    }
}
