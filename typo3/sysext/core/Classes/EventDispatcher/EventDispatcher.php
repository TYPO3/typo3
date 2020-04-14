<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Base PSR-14 event dispatcher which has only one listener provider, given at runtime
 * Is a singleton instance in order to be published once.
 */
class EventDispatcher implements EventDispatcherInterface, SingletonInterface
{
    /**
     * @var ListenerProviderInterface
     */
    protected $listenerProvider;

    public function __construct(ListenerProviderInterface $listenerProvider)
    {
        $this->listenerProvider = $listenerProvider;
    }

    /**
     * @inheritdoc
     */
    public function dispatch(object $event)
    {
        // If the event is already stopped, nothing to do here.
        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            return $event;
        }
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            $listener($event);
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
        }
        return $event;
    }
}
