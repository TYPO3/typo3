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
 * Reduce tt_content colPos items if needed: When the pages backend layout does not allow
 * the current content element type (CType value) in a colPos (via "allowedContentTypes" and
 * "disallowedContentTypes" backend layout column configuration), then the content element
 * can not be switched to this colPos. The implementation reduces colPos items accordingly.
 *
 * @internal
 */
final readonly class TcaTtContentColPosItemsRestrictionByBackendLayout implements FormDataProviderInterface
{
    public function __construct(
        private BackendLayoutView $backendLayoutView,
    ) {}

    public function addData(array $result): array
    {
        if ($result['tableName'] !== 'tt_content'
            || empty($result['databaseRow']['CType'])
            || !empty($result['isInlineChild'])
            || empty($result['processedTca']['columns']['colPos']['config']['type'])
            || $result['processedTca']['columns']['colPos']['config']['type'] !== 'select'
            || empty($result['processedTca']['columns']['colPos']['config']['items'])
            || !is_array($result['processedTca']['columns']['colPos']['config']['items'])
        ) {
            // tt_content colPos should be select. Return early if it isn't for some reason, or if tt_content is an inline child
            return $result;
        }
        $languageService = $this->getLanguageService();
        $pageId = !empty($result['effectivePid']) ? (int)$result['effectivePid'] : (int)$result['databaseRow']['pid'];
        $backendLayout = $this->backendLayoutView->getBackendLayoutForPage($pageId);
        $recordType = $result['databaseRow']['CType'][0] ?? '';
        $currentColPos = (int)($result['databaseRow']['colPos'][0] ?? 0);
        foreach ($result['processedTca']['columns']['colPos']['config']['items'] as $key => $item) {
            $itemColPosValue = (int)($item['value']);
            $columnConfiguration = $this->backendLayoutView->getColPosConfigurationForPage($backendLayout, $itemColPosValue, $pageId, $result['request']);
            if (empty($columnConfiguration)) {
                continue;
            }
            if (!empty($columnConfiguration['allowedContentTypes'])) {
                $allowedContentTypes = GeneralUtility::trimExplode(',', $columnConfiguration['allowedContentTypes'], true);
                if (!in_array($recordType, $allowedContentTypes, true)
                    && $currentColPos !== $itemColPosValue
                ) {
                    unset($result['processedTca']['columns']['colPos']['config']['items'][$key]);
                }
                if (!in_array($recordType, $allowedContentTypes, true)
                    && $currentColPos === $itemColPosValue
                ) {
                    $newLabel = sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'), $currentColPos);
                    $result['processedTca']['columns']['colPos']['config']['items'][$key]['label'] = $newLabel;
                }
            }
            if (!empty($columnConfiguration['disallowedContentTypes'])) {
                $disallowedContentTypes = GeneralUtility::trimExplode(',', $columnConfiguration['disallowedContentTypes'], true);
                if (in_array($recordType, $disallowedContentTypes, true)
                    && $currentColPos !== $itemColPosValue
                ) {
                    unset($result['processedTca']['columns']['colPos']['config']['items'][$key]);
                }
                if (in_array($recordType, $disallowedContentTypes, true)
                    && $currentColPos === $itemColPosValue
                ) {
                    $newLabel = sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'), $currentColPos);
                    $result['processedTca']['columns']['colPos']['config']['items'][$key]['label'] = $newLabel;
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
