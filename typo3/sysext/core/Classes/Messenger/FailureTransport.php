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

namespace TYPO3\CMS\Core\Messenger;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Messenger\Event\ResolveFailureTransportEvent;

/**
 * Resolves the failure transport of a receiver from
 * $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger'], adjustable per receiver
 * by listening to the ResolveFailureTransportEvent.
 *
 * @internal
 */
final readonly class FailureTransport
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function globalName(): ?string
    {
        $name = $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] ?? '';
        return is_string($name) && $name !== '' ? $name : null;
    }

    public function forReceiver(string $receiver): ?string
    {
        $event = new ResolveFailureTransportEvent($receiver, $this->configuredForReceiver($receiver));
        $this->eventDispatcher->dispatch($event);
        return $event->getFailureTransportName();
    }

    /**
     * Failure transport for a receiver as configured (per-transport override or
     * global default), before the resolve event is applied.
     */
    private function configuredForReceiver(string $receiver): ?string
    {
        $transportConfiguration = $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['transports'][$receiver] ?? null;
        if (is_array($transportConfiguration) && array_key_exists('failure_transport', $transportConfiguration)) {
            $name = $transportConfiguration['failure_transport'];
            return is_string($name) && $name !== '' ? $name : null;
        }
        return $this->globalName();
    }
}
