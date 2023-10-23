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

namespace TYPO3\CMS\Webhooks\Tca\ItemsProcFunc;

use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Webhooks\WebhookTypesRegistry;

/**
 * Custom TCA renderings and itemsProcFunc.
 *
 * @internal not part of TYPO3's Core API
 */
class WebhookTypesItemsProcFunc
{
    public function __construct(
        private readonly WebhookTypesRegistry $webhookTypesRegistry,
        private readonly LanguageServiceFactory $languageServiceFactory
    ) {}

    public function getWebhookTypes(&$fieldDefinition): void
    {
        $lang = $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);
        foreach ($this->webhookTypesRegistry->getAvailableWebhookTypes() as $identifier => $webhookType) {
            $fieldDefinition['items'][] = [
                'label' => $lang->sL($webhookType->getDescription()) ?: $webhookType->getDescription(),
                'value' => $identifier,
            ];
        }
    }
}
