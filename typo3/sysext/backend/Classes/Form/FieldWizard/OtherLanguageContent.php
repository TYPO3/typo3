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
        $l10nDisplay = $this->data['parameterArray']['fieldConf']['l10n_display'] ?? '';
        $defaultLanguageRow = $this->data['defaultLanguageRow'] ?? null;
        if (!is_array($defaultLanguageRow)
            || GeneralUtility::inList($l10nDisplay, 'hideDiff')
            || GeneralUtility::inList($l10nDisplay, 'defaultAsReadonly')
            || $fieldConfig['config']['type'] === 'inline'
            || $fieldConfig['config']['type'] === 'flex'
            || ($fieldConfig['config']['type'] === 'group' && isset($fieldConfig['config']['MM']))
            || ($fieldConfig['config']['type'] === 'select' && isset($fieldConfig['config']['MM']))
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
        );
        if ($defaultLanguageValue !== '') {
            $html[] = '<div class="t3-form-original-language">';
            $html[] =   $iconFactory->getIcon($this->data['systemLanguageRows'][0]['flagIconIdentifier'], Icon::SIZE_SMALL)->render();
            $html[] =   $this->previewFieldValue($defaultLanguageValue, $fieldConfig, $fieldName);
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
            );
            if ($defaultLanguageValue !== '') {
                $html[] = '<div class="t3-form-original-language">';
                $html[] =   $iconFactory->getIcon($this->data['systemLanguageRows'][$previewLanguage['sys_language_uid']]['flagIconIdentifier'], Icon::SIZE_SMALL)->render();
                $html[] =   $this->previewFieldValue($defaultLanguageValue, $fieldConfig, $fieldName);
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
     * @param array $config Configuration for field.
     * @param string $field Name of field.
     * @return string HTML formatted output
     */
    protected function previewFieldValue($value, $config, $field = '')
    {
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Deprecation logged by TcaMigration class.
        $value = (string)$value;
        if ($config['config']['type'] === 'group'
            && ($config['config']['internal_type'] === 'file' || $config['config']['internal_type'] === 'file_reference')
        ) {
            // Ignore upload folder if internal_type is file_reference
            if ($config['config']['internal_type'] === 'file_reference') {
                $config['config']['uploadfolder'] = '';
            }
            $table = 'tt_content';
            // Making the array of file items:
            $itemArray = GeneralUtility::trimExplode(',', $value, true);
            // Showing thumbnails:
            $imgs = [];
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            foreach ($itemArray as $imgRead) {
                $imgParts = explode('|', $imgRead);
                $imgPath = rawurldecode($imgParts[0]);
                $rowCopy = [];
                $rowCopy[$field] = $imgPath;
                // Icon + click menu:
                $absFilePath = GeneralUtility::getFileAbsFileName($config['config']['uploadfolder'] ? $config['config']['uploadfolder'] . '/' . $imgPath : $imgPath);
                $fileInformation = pathinfo($imgPath);
                $title = $fileInformation['basename'] . ($absFilePath && @is_file($absFilePath))
                    ? ' (' . GeneralUtility::formatSize(filesize($absFilePath)) . ')'
                    : ' - FILE NOT FOUND!';
                $fileIcon = '<span title="' . htmlspecialchars($title) . '">' . $iconFactory->getIconForFileExtension($fileInformation['extension'], Icon::SIZE_SMALL)->render() . '</span>';
                $imgs[] =
                    '<span class="text-nowrap">' .
                    BackendUtility::thumbCode(
                        $rowCopy,
                        $table,
                        $field,
                        '',
                        '',
                        $config['config']['uploadfolder'],
                        0,
                        ' align="middle"'
                    ) .
                    ($absFilePath ? BackendUtility::wrapClickMenuOnIcon($fileIcon, $absFilePath) : $fileIcon) .
                    $imgPath .
                    '</span>';
            }
            return implode('<br />', $imgs);
        }
        return nl2br(htmlspecialchars($value));
    }
}
