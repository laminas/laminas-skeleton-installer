<?php
/**
 * @see       https://github.com/zendframework/zend-skeleton-installer for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-skeleton-installer/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\SkeletonInstaller\TestAsset;

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
