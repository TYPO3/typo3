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

namespace TYPO3\CMS\Webhooks\Message;

use TYPO3\CMS\Core\Attribute\WebhookMessage;
use TYPO3\CMS\Core\Messaging\WebhookMessageInterface;
use TYPO3\CMS\Core\Resource\Event\BeforeFileDeletedEvent;

/**
 * A message that is triggered when a file was deleted.
 *
 * @internal not part of TYPO3 Core API
 */
#[WebhookMessage(
    identifier: 'typo3/file-removed',
    description: 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.webhook_type.typo3-file-removed'
)]
final class FileRemovedMessage implements WebhookMessageInterface
{
    public function __construct(
        private readonly int $storageUid,
        private readonly string $identifier,
        private readonly string $publicUrl
    ) {}

    public static function createFromEvent(BeforeFileDeletedEvent $event): self
    {
        $file = $event->getFile();
        return new self($file->getStorage()->getUid(), $file->getIdentifier(), $file->getPublicUrl());
    }

    public function jsonSerialize(): array
    {
        return [
            'storage' => $this->storageUid,
            'identifier' => $this->identifier,
            'url' => $this->publicUrl,
        ];
    }
}
