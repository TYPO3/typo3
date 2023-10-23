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

namespace TYPO3\CMS\Redirects\Message;

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Attribute\WebhookMessage;
use TYPO3\CMS\Core\Messaging\WebhookMessageInterface;
use TYPO3\CMS\Redirects\Event\RedirectWasHitEvent;

#[WebhookMessage(
    identifier: 'typo3/redirect-was-hit',
    description: 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.webhook_type.typo3-redirect-was-hit'
)]
final class RedirectWasHitMessage implements WebhookMessageInterface
{
    public function __construct(
        private readonly UriInterface $sourceUrl,
        private readonly UriInterface $targetUrl,
        private readonly int $statusCode,
        private readonly array $matchedRedirect,
    ) {}

    public static function createFromEvent(RedirectWasHitEvent $event): self
    {
        return new self(
            $event->getRequest()->getUri(),
            $event->getTargetUrl(),
            $event->getResponse()->getStatusCode(),
            $event->getMatchedRedirect(),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'sourceUrl' => (string)$this->sourceUrl,
            'targetUrl' => (string)$this->targetUrl,
            'statusCode' => $this->statusCode,
            'redirect' => $this->matchedRedirect,
        ];
    }
}
