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

namespace TYPO3\CMS\Webhooks\ConfigurationModuleProvider;

use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\AbstractProvider;
use TYPO3\CMS\Webhooks\WebhookTypesRegistry;

/**
 * Shows configured webhook types in the EXT:lowlevel configuration module.
 *
 * @internal not part of TYPO3's Core API
 */
class WebhookTypesProvider extends AbstractProvider
{
    public function __construct(
        private readonly WebhookTypesRegistry $webhookTypesRegistry
    ) {
    }

    public function getConfiguration(): array
    {
        $configuration = [];
        foreach ($this->webhookTypesRegistry->getAvailableWebhookTypes() as $identifier => $webhookType) {
            $configuration[$identifier] = [
                'messageName' => $webhookType->getServiceName(),
                'description' => $webhookType->getDescription(),
                'connectedEvent' => $webhookType->getConnectedEvent() ?? 'none',
            ];
        }
        return $configuration;
    }
}
