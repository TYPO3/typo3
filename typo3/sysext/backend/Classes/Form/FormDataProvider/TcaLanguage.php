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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Resolve select items for the type="language" and set processed item list in processedTca
 */
class TcaLanguage extends AbstractItemProvider implements FormDataProviderInterface
{
    public function __construct(
        private readonly SiteFinder $siteFinder,
    ) {}

    /**
     * Fetch languages to add them as select item
     *
     * @throws \UnexpectedValueException
     */
    public function addData(array $result): array
    {
        $table = $result['tableName'];

        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (!isset($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'language') {
                continue;
            }

            // Save user defined items and reset the field config items array afterwards
            $userDefinedItems = $this->sanitizeItemArray($fieldConfig['config']['items'] ?? [], $table, $fieldName);
            $fieldConfig['config']['items'] = [];

            // Initialize site languages to be fetched
            $siteLanguages = [];

            if (($result['effectivePid'] ?? 0) === 0 || !($result['site'] ?? null) instanceof Site) {
                // In case we deal with a pid=0 record or a record on a page outside
                // of a site config, all languages from all sites should be added.
                foreach ($this->siteFinder->getAllSites() as $site) {
                    // Add ALL languages from ALL sites
                    foreach ($site->getAllLanguages() as $languageId => $language) {
                        if (isset($siteLanguages[$languageId])) {
                            // Language already provided by another site, just add the label separately
                            $siteLanguages[$languageId]['title'] .= ', ' . $language->getTitle() . ' [Site: ' . $site->getIdentifier() . ']';
                        } else {
                            $siteLanguages[$languageId] = [
                                'title' => $language->getTitle() . ' [Site: ' . $site->getIdentifier() . ']',
                                'flagIconIdentifier' => $language->getFlagIdentifier(),
                            ];
                        }
                    }
                }
                ksort($siteLanguages);
            } elseif (($result['systemLanguageRows'] ?? []) !== []) {
                // Add system languages available for the current site
                foreach ($result['systemLanguageRows'] as $languageId => $language) {
                    if ($languageId !== -1) {
                        $siteLanguages[$languageId] = [
                            'title' => $language['title'],
                            'flagIconIdentifier' => $language['flagIconIdentifier'],
                        ];
                    }
                }
            }

            if ($siteLanguages !== []) {
                // In case siteLanguages are available, add the "site languages" group
                $fieldConfig['config']['items'] = [
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.siteLanguages',
                        'value' => '--div--',
                    ],
                ];
                // Add the fetched site languages to the field config items array
                foreach ($siteLanguages as $languageId => $language) {
                    $fieldConfig['config']['items'][] = [
                        'label' => $language['title'],
                        'value' => $languageId,
                        'icon' => $language['flagIconIdentifier'],
                    ];
                }
            }

            // Add the "special" group for "ALL" and / or user defined items
            if (($table !== 'pages' && isset($result['systemLanguageRows'][-1])) || $userDefinedItems !== []) {
                $fieldConfig['config']['items'][] = [
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.specialLanguages',
                    'value' => '--div--',
                ];
            }
            // Add "-1" for all TCA records except pages in case the user is allowed to.
            // The item is added to the "special" group, in order to not provide it as default by accident.
            if ($table !== 'pages' && isset($result['systemLanguageRows'][-1])) {
                $fieldConfig['config']['items'][] = [
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages',
                    'value' => -1,
                    'icon' => 'flags-multiple',
                ];
            }

            // Add user defined items again so they are in the "special" group
            $fieldConfig['config']['items'] = array_merge($fieldConfig['config']['items'], $userDefinedItems);

            // Respect TSconfig options
            $fieldConfig['config']['items'] = $this->removeItemsByKeepItemsPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->addItemsFromPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);
            $fieldConfig['config']['items'] = $this->removeItemsByRemoveItemsPageTsConfig($result, $fieldName, $fieldConfig['config']['items']);

            // In case no items are set at this point, we can write this back and continue with the next column
            if ($fieldConfig['config']['items'] === []) {
                $result['processedTca']['columns'][$fieldName] = $fieldConfig;
                continue;
            }

            // Check current database value
            $currentDatabaseValue = (int)($result['databaseRow'][$fieldName] ?? 0);
            if (!in_array($currentDatabaseValue, array_map(intval(...), array_column($fieldConfig['config']['items'], 'value')), true)) {
                // Current value is invalid, so add it with a proper message at the top
                $fieldConfig['config']['items'] = $this->addInvalidItem($result, $table, $fieldName, $currentDatabaseValue, $fieldConfig['config']['items']);
            }

            // Reinitialize array keys
            $fieldConfig['config']['items'] = array_values($fieldConfig['config']['items']);

            // In case the last element is a divider, remove it
            if ((string)($fieldConfig['config']['items'][array_key_last($fieldConfig['config']['items'])]['value'] ?? '') === '--div--') {
                array_pop($fieldConfig['config']['items']);
            }

            // Translate labels
            $fieldConfig['config']['items'] = $this->translateLabels($result, $fieldConfig['config']['items'], $table, $fieldName);

            // Add icons
            $fieldConfig['config']['items'] = $this->addIconFromAltIcons($result, $fieldConfig['config']['items'], $table, $fieldName);

            $result['processedTca']['columns'][$fieldName] = $fieldConfig;
        }

        return $result;
    }

    protected function addInvalidItem(
        array $result,
        string $table,
        string $fieldName,
        int $invalidValue,
        array $items
    ): array {
        // Early return if there are no items or invalid values should not be displayed
        if (($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['disableNoMatchingValueElement'] ?? false)
            || ($result['processedTca']['columns'][$fieldName]['config']['disableNoMatchingValueElement'] ?? false)
        ) {
            return $items;
        }

        $noMatchingLabel = isset($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['noMatchingValue_label'])
            ? $this->getLanguageService()->sL(trim($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['noMatchingValue_label']))
            : '[ ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue') . ' ]';

        // Add the invalid value at the top
        array_unshift($items, ['label' => @sprintf($noMatchingLabel, $invalidValue), 'value' => $invalidValue, 'icon' => null]);

        return $items;
    }
}
