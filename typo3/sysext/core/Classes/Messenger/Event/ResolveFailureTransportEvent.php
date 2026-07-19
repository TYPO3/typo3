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

namespace TYPO3\CMS\Core\Messenger\Event;

/**
 * Dispatched while resolving the messenger failure transport a receiver's failed
 * messages are parked in. Listeners may override the resolved failure transport
 * name for a receiver - for example to route a specific transport's failures to a
 * dedicated failure transport, or to opt a transport out by setting null.
 */
final class ResolveFailureTransportEvent
{
    public function __construct(
        private readonly string $receiverName,
        private ?string $failureTransportName,
    ) {}

    /**
     * Name of the receiver (transport) whose failed message is being handled.
     */
    public function getReceiverName(): string
    {
        return $this->receiverName;
    }

    /**
     * Name of the failure transport resolved so far, or null when none applies.
     */
    public function getFailureTransportName(): ?string
    {
        return $this->failureTransportName;
    }

    /**
     * Override the failure transport for the receiver. Null disables the failure
     * transport for this receiver.
     */
    public function setFailureTransportName(?string $failureTransportName): void
    {
        $this->failureTransportName = $failureTransportName;
    }
}
