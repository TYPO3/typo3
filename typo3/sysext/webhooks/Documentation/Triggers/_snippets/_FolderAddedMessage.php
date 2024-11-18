<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Webhooks\Message;

use TYPO3\CMS\Core\Attribute\WebhookMessage;
use TYPO3\CMS\Core\Messaging\WebhookMessageInterface;
use TYPO3\CMS\Core\Resource\Event\AfterFolderAddedEvent;

#[WebhookMessage(
    identifier: 'typo3/folder-added',
    description: 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.webhook_type.typo3-folder-added'
)]
final class FolderAddedMessage implements WebhookMessageInterface
{
    public function __construct(
        private readonly int $storageUid,
        private readonly string $identifier,
        private readonly string $publicUrl
    ) {}

    public static function createFromEvent(AfterFolderAddedEvent $event): self
    {
        $folder = $event->getFolder();
        return new self($folder->getStorage()->getUid(), $folder->getIdentifier(), $folder->getPublicUrl());
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
