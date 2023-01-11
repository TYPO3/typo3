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

namespace TYPO3\CMS\Webhooks;

use TYPO3\CMS\Webhooks\Model\WebhookType;

/**
 * Registry contains all possible webhooks types which are available to the system
 * To register a webhook your class must be tagged with the Webhook attribute.
 *
 * @internal not part of TYPO3's Core API
 */
class WebhookTypesRegistry
{
    /**
     * @var WebhookType[]
     */
    private array $webhookTypes = [];

    /**
     * @return WebhookType[]
     */
    public function getAvailableWebhookTypes(): array
    {
        return $this->webhookTypes;
    }

    /**
     * Whether a registered webhook type exists
     */
    public function hasWebhookType(string $type): bool
    {
        return isset($this->webhookTypes[$type]);
    }

    public function getWebhookByType(string $type): WebhookType
    {
        if (!$this->hasWebhookType($type)) {
            throw new \UnexpectedValueException('No webhook with type ' . $type . ' registered.', 1679348837);
        }

        return $this->webhookTypes[$type];
    }

    public function getWebhookByEventIdentifier(string $eventIdentifier): ?WebhookType
    {
        foreach ($this->webhookTypes as $type) {
            if ($type->getConnectedEvent() === $eventIdentifier) {
                return $type;
            }
        }
        return null;
    }

    public function addWebhookType(string $identifier, string $description, string $serviceName, string $factoryMethod, ?string $connectedEvent): void
    {
        $this->webhookTypes[$identifier] = new WebhookType($identifier, $description, $serviceName, $factoryMethod, $connectedEvent);
    }
}
