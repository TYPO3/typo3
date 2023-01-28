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

namespace TYPO3\CMS\Backend\Configuration\TCA;

use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class provides items processor functions for the usage in TCA definition
 * @internal
 */
class ItemsProcessorFunctions
{
    /**
     * Return languages found in already existing site configurations,
     * sorted by their value. In case the same language is used with
     * different titles, they will be added to the items label field.
     * Additionally, a placeholder value is added to allow the creation
     * of new site languages.
     */
    public function populateAvailableLanguagesFromSites(array &$fieldDefinition): void
    {
        foreach (GeneralUtility::makeInstance(SiteFinder::class)->getAllSites() as $site) {
            foreach ($site->getAllLanguages() as $languageId => $language) {
                if (!isset($fieldDefinition['items'][$languageId])) {
                    $fieldDefinition['items'][$languageId] = [
                        'label' => $language->getTitle(),
                        'value' => $languageId,
                        'icon' => $language->getFlagIdentifier(),
                        'tempTitles' => [],
                    ];
                } elseif ($fieldDefinition['items'][$languageId]['label'] !== $language->getTitle()) {
                    // Temporarily store different titles
                    $fieldDefinition['items'][$languageId]['tempTitles'][] = $language->getTitle();
                }
            }
        }

        if (!isset($fieldDefinition['items'][0])) {
            // Since TcaSiteLanguage has a special behaviour, enforcing the
            // default language ("0") to be always added to the site configuration,
            // we have to add it to the available items, in case it is not already
            // present. This only happens for the first ever created site configuration.
            $fieldDefinition['items'][] = ['label' => 'Default', 'value' => 0, 'icon' => '', 'tempTitles' => []];
        }

        ksort($fieldDefinition['items']);

        // Build the final language label
        foreach ($fieldDefinition['items'] as &$language) {
            $language['label'] .= ' [' . $language['value'] . ']';
            if ($language['tempTitles'] !== []) {
                $language['label'] .= ' (' . implode(',', array_unique($language['tempTitles'])) . ')';
                // Unset the temporary title "storage"
                unset($language['tempTitles']);
            }
        }
        unset($language);

        // Add PHP_INT_MAX as last - placeholder - value to allow creation of new records
        // with the "Create new" button, which is usually not possible in "selector" mode.
        // Note: The placeholder will never be displayed in the selector.
        $fieldDefinition['items'] = array_values(
            array_merge($fieldDefinition['items'], [['label' => 'Placeholder', 'value' => PHP_INT_MAX]])
        );
    }

    /**
     * Return language items for use in site_languages.fallbacks
     */
    public function populateFallbackLanguages(array &$fieldDefinition): void
    {
        foreach (GeneralUtility::makeInstance(SiteFinder::class)->getAllSites() as $site) {
            foreach ($site->getAllLanguages() as $languageId => $language) {
                if (isset($fieldDefinition['row']['languageId'][0])
                    && (int)$fieldDefinition['row']['languageId'][0] === $languageId
                ) {
                    // Skip current language id
                    continue;
                }
                if (!isset($fieldDefinition['items'][$languageId])) {
                    $fieldDefinition['items'][$languageId] = [
                        'label' => $language->getTitle(),
                        'value' => $languageId,
                        'icon' => $language->getFlagIdentifier(),
                        'tempTitles' => [],
                    ];
                } elseif ($fieldDefinition['items'][$languageId]['label'] !== $language->getTitle()) {
                    // Temporarily store different titles
                    $fieldDefinition['items'][$languageId]['tempTitles'][] = $language->getTitle();
                }
            }
        }
        ksort($fieldDefinition['items']);

        // Build the final language label
        foreach ($fieldDefinition['items'] as &$language) {
            if ($language['tempTitles'] !== []) {
                $language['label'] .= ' (' . implode(',', array_unique($language['tempTitles'])) . ')';
                // Unset the temporary title "storage"
                unset($language['tempTitles']);
            }
        }
        unset($language);

        $fieldDefinition['items'] = array_values($fieldDefinition['items']);
    }
}
