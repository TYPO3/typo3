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

namespace TYPO3\CMS\Core\Messenger\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageSkipEvent;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener as SymfonySendFailedMessageToFailureTransportListener;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Messenger\FailureTransportLocator;

/**
 * Registers the Symfony listener, which is an event subscriber, as PSR-14
 * listener in the order of its subscriber priority.
 *
 * @internal
 */
final readonly class SendFailedMessageToFailureTransportListener
{
    private SymfonySendFailedMessageToFailureTransportListener $listener;

    public function __construct(
        FailureTransportLocator $failureSenders,
        LoggerInterface $logger,
    ) {
        $this->listener = new SymfonySendFailedMessageToFailureTransportListener($failureSenders, $logger);
    }

    #[AsEventListener(identifier: 'messenger-send-failed-to-failure-transport')]
    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $this->listener->onMessageFailed($event);
    }

    #[AsEventListener(identifier: 'messenger-send-skipped-to-failure-transport')]
    public function onMessageSkip(WorkerMessageSkipEvent $event): void
    {
        $this->listener->onMessageSkip($event);
    }
}
