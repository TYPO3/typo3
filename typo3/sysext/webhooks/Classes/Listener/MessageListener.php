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

namespace TYPO3\CMS\Webhooks\Listener;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Webhooks\Message\WebhookMessageFactory;

/**
 * Listens to registered PSR-14 events and creates a message out of it.
 * The MessageListener is automatically attached to the respecting Events by DI.
 *
 * Messages are dispatched to the message bus after creation.
 *
 * @internal not part of TYPO3 Core API
 */
class MessageListener
{
    public function __construct(
        protected readonly MessageBusInterface $bus,
        protected readonly WebhookMessageFactory $messageFactory,
        protected readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(mixed $object): void
    {
        $message = $this->messageFactory->createMessageFromEvent($object);
        if ($message === null) {
            return;
        }
        try {
            $this->bus->dispatch($message);
        } catch (\Throwable $e) {
            // At the moment we ignore every exception here, but we log them.
            // An exception here means that an error happens while sending the webhook,
            // and we should not block the execution of other configured webhooks.
            // This can happen if no transport is configured, and the message is handled directly.
            $this->logger->error(get_class($message) . ': ' . $e->getMessage());
        }
    }
}
