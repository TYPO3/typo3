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

namespace TYPO3\CMS\Backend\Form\FieldWizard;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render values of "other" languages. If editing a localized row, this is typically
 * the content value of the according default record, but it may render field values
 * of other languages too, depending on configuration.
 */
class OtherLanguageContent extends AbstractNode
{
    /**
     * Render other language content if enabled.
     *
     * @return array
     */
    public function render(): array
    {
        $result = $this->initializeResultArray();

        $fieldName = $this->data['fieldName'];
        $fieldConfig = $this->data['processedTca']['columns'][$fieldName];
        $fieldType = $fieldConfig['config']['type'];
        $l10nDisplay = $this->data['parameterArray']['fieldConf']['l10n_display'] ?? '';
        $defaultLanguageRow = $this->data['defaultLanguageRow'] ?? null;
        if (!is_array($defaultLanguageRow)
            || GeneralUtility::inList($l10nDisplay, 'hideDiff')
            || GeneralUtility::inList($l10nDisplay, 'defaultAsReadonly')
            || $fieldType === 'inline'
            || $fieldType === 'flex'
            || (in_array($fieldType, ['select', 'category', 'group'], true) && isset($fieldConfig['config']['MM']))
        ) {
            // Early return if there is no default language row or the display is disabled
            return $result;
        }

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $table = $this->data['tableName'];
        $html = [];
        $defaultLanguageValue = BackendUtility::getProcessedValue(
            $table,
            $fieldName,
            $defaultLanguageRow[$fieldName],
            0,
            true,
            false,
            $defaultLanguageRow['uid'],
            true,
            $defaultLanguageRow['pid']
        ) ?? '';
        if ($defaultLanguageValue !== '') {
            $iconIdentifier = ($this->data['systemLanguageRows'][0]['flagIconIdentifier'] ?? false) ?: 'flags-multiple';
            $html[] = '<div class="t3-form-original-language">';
            $html[] =   $iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render();
            $html[] =   $this->previewFieldValue($defaultLanguageValue);
            $html[] = '</div>';
        }
        $additionalPreviewLanguages = $this->data['additionalLanguageRows'];
        foreach ($additionalPreviewLanguages as $previewLanguage) {
            $defaultLanguageValue = BackendUtility::getProcessedValue(
                $table,
                $fieldName,
                $previewLanguage[$fieldName],
                0,
                true
            ) ?? '';
            if ($defaultLanguageValue !== '') {
                $html[] = '<div class="t3-form-original-language">';
                $html[] =   $iconFactory->getIcon($this->data['systemLanguageRows'][$previewLanguage['sys_language_uid']]['flagIconIdentifier'], Icon::SIZE_SMALL)->render();
                $html[] =   $this->previewFieldValue($defaultLanguageValue);
                $html[] = '</div>';
            }
        }
        $result['html'] = implode(LF, $html);
        return $result;
    }

    /**
     * Rendering preview output of a field value which is not shown as a form field but just outputted.
     *
     * @param string $value The value to output
     * @return string HTML formatted output
     */
    protected function previewFieldValue($value)
    {
        return nl2br(htmlspecialchars((string)$value));
    }
}
