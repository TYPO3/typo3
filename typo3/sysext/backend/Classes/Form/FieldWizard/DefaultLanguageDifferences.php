<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Form\FieldWizard;

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

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Renders the diff-view of default language record content compared with what the record was originally
 * translated from. Will render content if any is found in the internal array.
 *
 * This is typically used of renderTypes that are based on text input
 */
class DefaultLanguageDifferences extends AbstractNode
{
    /**
     * Render the diff view if enabled
     *
     * @return array Result array
     */
    public function render(): array
    {
        $result = $this->initializeResultArray();

        $row = $this->data['databaseRow'];
        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $fieldConfig = $this->data['processedTca']['columns'][$fieldName];
        $l10nDisplay = $this->data['parameterArray']['fieldConf']['l10n_display'] ?? '';
        $defaultLanguageRow = $this->data['defaultLanguageRow'] ?? null;
        $defaultLanguageDiffRow = $this->data['defaultLanguageDiffRow'][$table . ':' . $row['uid']] ?? null;

        if (!is_array($defaultLanguageDiffRow)
            || GeneralUtility::inList($l10nDisplay, 'hideDiff')
            || GeneralUtility::inList($l10nDisplay, 'defaultAsReadonly')
            || !isset($defaultLanguageDiffRow[$fieldName])
            || $fieldConfig['config']['type'] === 'inline'
            || $fieldConfig['config']['type'] === 'flex'
        ) {
            // Early return if there is no diff row or if display is disabled
            return $result;
        }

        $languageService = $this->getLanguageService();
        $html = [];
        if ((string)$defaultLanguageDiffRow[$fieldName] !== (string)$defaultLanguageRow[$fieldName]) {
            // Create diff-result:
            $diffUtility = GeneralUtility::makeInstance(DiffUtility::class);
            $diffUtility->stripTags = false;
            $diffResult = $diffUtility->makeDiffDisplay(
                BackendUtility::getProcessedValue($table, $fieldName, $defaultLanguageDiffRow[$fieldName], 0, 1),
                BackendUtility::getProcessedValue($table, $fieldName, $defaultLanguageRow[$fieldName], 0, 1)
            );
            $html[] = '<div class="t3-form-original-language-diff">';
            $html[] =   '<div class="t3-form-original-language-diffheader">';
            $html[] =       htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.changeInOrig'));
            $html[] =   '</div>';
            $html[] =   '<div class="t3-form-original-language-diffcontent">';
            $html[] =       '<div class="diff">';
            $html[] =           '<div class="diff-item">';
            $html[] =               '<div class="diff-item-result diff-item-result-inline">' . $diffResult . '</div>';
            $html[] =           '</div>';
            $html[] =       '</div>';
            $html[] =   '</div>';
            $html[] =  '</div>';
        }
        $result['html'] = implode(LF, $html);
        return $result;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
