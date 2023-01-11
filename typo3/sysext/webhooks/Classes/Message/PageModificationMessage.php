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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Messaging\WebhookMessageInterface;

/**
 * @internal not part of TYPO3's Core API
 */
#[WebhookMessage(
    identifier: 'typo3/content/page-modification',
    description: 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.webhook_type.typo3-content-page-modification'
)]
final class PageModificationMessage implements WebhookMessageInterface
{
    public function __construct(
        private readonly string $action,
        private readonly int $uid,
        private readonly array $record,
        private readonly string $url,
        private readonly string $siteIdentifier,
        private readonly ?BackendUserAuthentication $author = null,
        private readonly ?array $modifiedFields = null
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = [
            'action' => $this->action,
            'identifier' => $this->uid,
            'record' => $this->record,
            'url' => $this->url,
            'site' => $this->siteIdentifier,
            'workspace' => $this->record['t3ver_wsid'] ?? 0,
        ];
        if ($this->author instanceof BackendUserAuthentication) {
            $data['author'] = [
                'uid' => $this->author->user['uid'],
                'username' => $this->author->user['username'],
                'isAdmin' => $this->author->isAdmin(),
            ];
        }
        if (is_array($this->modifiedFields)) {
            $data['changedFields'] = $this->modifiedFields;
        }
        return $data;
    }
}
