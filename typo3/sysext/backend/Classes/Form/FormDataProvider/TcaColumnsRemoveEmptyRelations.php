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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Schema\LanguageMarker;

/**
 * Remove or disable fields (select, category, language) in the form when no
 * selectable items are available, or when only a single choice exists (language
 * fields).
 *
 * For regular users, the field is removed entirely. When backend debug mode is
 * enabled ($GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = true), the field is
 * kept as readOnly with an info badge, so admins can identify configuration
 * issues such as missing records or restrictive TSconfig.
 *
 * Existing database values are not affected: fields not rendered in the form
 * are simply not submitted, so DataHandler preserves their stored values.
 *
 * Fields can opt out via TCA config 'showIfEmpty' => true.
 */
readonly class TcaColumnsRemoveEmptyRelations implements FormDataProviderInterface
{
    public function addData(array $result): array
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (!$this->isApplicableField($fieldConfig)) {
                continue;
            }

            if ($fieldConfig['config']['showIfEmpty'] ?? false) {
                continue;
            }

            $type = $fieldConfig['config']['type'] ?? '';

            if ($type === 'language') {
                if ($this->hasMultipleLanguageChoices($fieldConfig)) {
                    continue;
                }
            } elseif ($this->hasMeaningfulItems($fieldConfig)) {
                continue;
            }

            if ($this->getBackendUser()->shallDisplayDebugInformation()) {
                $result['processedTca']['columns'][$fieldName]['config']['readOnly'] = true;
                $result['processedTca']['columns'][$fieldName]['config']['fieldInformation']['noSelectableItemsAvailable'] = [
                    'renderType' => 'noSelectableItemsAvailable',
                ];
            } else {
                unset($result['processedTca']['columns'][$fieldName]);
            }
        }

        return $result;
    }

    /**
     * Check if this field type should be handled by this provider.
     */
    private function isApplicableField(array $fieldConfig): bool
    {
        return in_array($fieldConfig['config']['type'] ?? '', ['select', 'category', 'language'], true);
    }

    /**
     * Check if the field has meaningful selectable items.
     *
     * For fields with foreign_table, only items from the foreign table (positive
     * integer UIDs) count. Static items like "Hide at login" (-1) or dividers
     * are not meaningful on their own — they require actual foreign records to
     * be useful (e.g., access restriction options require fe_groups to exist,
     * otherwise there can be no frontend login).
     */
    private function hasMeaningfulItems(array $fieldConfig): bool
    {
        $items = $fieldConfig['config']['items'] ?? [];
        $hasForeignTable = !empty($fieldConfig['config']['foreign_table']);

        foreach ($items as $item) {
            $value = (string)($item['value'] ?? $item[1] ?? '');
            if ($value === '' || $value === '--div--') {
                continue;
            }
            if ($hasForeignTable) {
                if ((int)$value > 0) {
                    return true;
                }
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a language field has more than one real language choice.
     * Filters out --div-- separators and the -1 "All languages" special item,
     * since on a single-language site neither provides a meaningful choice.
     */
    private function hasMultipleLanguageChoices(array $fieldConfig): bool
    {
        $items = $fieldConfig['config']['items'] ?? [];
        $realLanguageCount = 0;
        foreach ($items as $item) {
            $value = $item['value'] ?? '';
            if ((string)$value === '--div--' || (int)$value === LanguageMarker::ALL_LANGUAGES) {
                continue;
            }
            $realLanguageCount++;
            if ($realLanguageCount > 1) {
                return true;
            }
        }
        return false;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
