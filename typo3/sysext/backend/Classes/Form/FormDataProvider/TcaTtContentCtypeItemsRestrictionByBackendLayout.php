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
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Reduce tt_content CType items if needed: When the pages backend layout does not allow
 * content element types in current record colPos (via "allowedContentTypes" and
 * "disallowedContentTypes" backend layout column configuration), then the content element
 * can not be switched into those types. The implementation reduces CType items accordingly.
 *
 * @internal
 */
final readonly class TcaTtContentCtypeItemsRestrictionByBackendLayout implements FormDataProviderInterface
{
    public function __construct(
        private BackendLayoutView $backendLayoutView,
    ) {}

    public function addData(array $result): array
    {
        if ($result['tableName'] !== 'tt_content'
            || empty($result['databaseRow']['CType'])
            || !empty($result['isInlineChild'])
            || empty($result['processedTca']['columns']['CType']['config']['items'])
        ) {
            return $result;
        }
        $languageService = $this->getLanguageService();
        $pageId = !empty($result['effectivePid']) ? (int)$result['effectivePid'] : (int)$result['databaseRow']['pid'];
        $backendLayout = $this->backendLayoutView->getBackendLayoutForPage($pageId);
        if (is_array($result['databaseRow']['colPos'] ?? [])) {
            $currentColPosValue = (int)($result['databaseRow']['colPos'][0] ?? $result['processedTca']['columns']['colPos']['config']['default'] ?? 0);
        } else {
            $currentColPosValue = (int)($result['databaseRow']['colPos'] ?? $result['processedTca']['columns']['colPos']['config']['default'] ?? 0);
        }
        $columnConfiguration = $this->backendLayoutView->getColPosConfigurationForPage($backendLayout, $currentColPosValue, $pageId, $result['request']);
        $currentRecordType = $result['databaseRow']['CType'][0] ?? '';
        if (!empty($columnConfiguration['allowedContentTypes'])) {
            $allowedContentTypes = GeneralUtility::trimExplode(',', $columnConfiguration['allowedContentTypes'], true);
            foreach ($result['processedTca']['columns']['CType']['config']['items'] as $itemKey => $item) {
                if (!in_array($item['value'], $allowedContentTypes, true)
                    && $item['value'] !== '--div--'
                    && $item['value'] !== $currentRecordType
                ) {
                    unset($result['processedTca']['columns']['CType']['config']['items'][$itemKey]);
                }
                if (!in_array($item['value'], $allowedContentTypes, true)
                    && $item['value'] !== '--div--'
                    && $item['value'] === $currentRecordType
                ) {
                    $newLabel = sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'), $currentRecordType);
                    $result['processedTca']['columns']['CType']['config']['items'][$itemKey]['label'] = $newLabel;
                }
            }
        }
        if (!empty($columnConfiguration['disallowedContentTypes'])) {
            $disallowedContentTypes = GeneralUtility::trimExplode(',', $columnConfiguration['disallowedContentTypes'], true);
            foreach ($result['processedTca']['columns']['CType']['config']['items'] as $itemKey => $item) {
                if (in_array($item['value'], $disallowedContentTypes, true)
                    && $item['value'] !== $currentRecordType
                ) {
                    unset($result['processedTca']['columns']['CType']['config']['items'][$itemKey]);
                }
                if (in_array($item['value'], $disallowedContentTypes, true)
                    && $item['value'] === $currentRecordType
                ) {
                    $newLabel = sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'), $currentRecordType);
                    $result['processedTca']['columns']['CType']['config']['items'][$itemKey]['label'] = $newLabel;
                }
            }
        }
        return $result;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
