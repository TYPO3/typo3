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

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener as SymfonySendFailedMessageForRetryListener;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Messenger\RetryStrategyLocator;

/**
 * Registers the Symfony listener, which is an event subscriber, as PSR-14
 * listener in the order of its subscriber priority.
 *
 * @internal
 */
final readonly class SendFailedMessageForRetryListener
{
    private SymfonySendFailedMessageForRetryListener $listener;

    public function __construct(
        #[AutowireLocator('messenger.sender', indexAttribute: 'identifier')]
        ContainerInterface $senders,
        RetryStrategyLocator $retryStrategies,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->listener = new SymfonySendFailedMessageForRetryListener($senders, $retryStrategies, $logger, $eventDispatcher);
    }

    #[AsEventListener(identifier: 'messenger-send-failed-for-retry', before: 'messenger-send-failed-to-failure-transport')]
    public function __invoke(WorkerMessageFailedEvent $event): void
    {
        $this->listener->onMessageFailed($event);
    }
}
