<?php
/**
 * @see       https://github.com/zendframework/zend-skeleton-installer for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-skeleton-installer/blob/master/LICENSE.md New BSD License
 */

namespace Zend\SkeletonInstaller;

use Composer\Composer;
use Composer\EventDispatcher\Event;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Util\ProcessExecutor;

class BroadcastEventDispatcher extends EventDispatcher
{
    /** @var null|EventDispatcher */
    private $eventDispatcher;

    /** @var string[] */
    private $broadcastEvents;

    /**
     * @param EventDispatcher|null $eventDispatcher EventDispatcher of original Composer process
     * @param string[] $broadcastEvents List of events we want to pass to original EventDispatcher
     */
    public function __construct(
        Composer $composer,
        IOInterface $io,
        ProcessExecutor $process = null,
        EventDispatcher $eventDispatcher = null,
        array $broadcastEvents = []
    ) {
        parent::__construct($composer, $io, $process);

        $this->eventDispatcher = $eventDispatcher;
        $this->broadcastEvents = $broadcastEvents;
    }

    protected function doDispatch(Event $event)
    {
        if ($this->eventDispatcher && in_array($event->getName(), $this->broadcastEvents, true)) {
            return $this->eventDispatcher->doDispatch($event);
        }

        return parent::doDispatch($event);
    }
}
