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

namespace TYPO3\CMS\Backend\Preview;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Legacy preview rendering refactored from PageLayoutView.
 * Provided as default preview rendering mechanism via
 * StandardPreviewRendererResolver which detects the renderer
 * based on TCA configuration.
 *
 * Can be replaced and/or subclassed by custom implementations
 * by changing this TCA configuration.
 *
 * See also PreviewRendererInterface documentation.
 */
class StandardContentPreviewRenderer implements PreviewRendererInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function renderPageModulePreviewHeader(GridColumnItem $item): string
    {
        $record = $item->getRecord();
        $itemLabels = $item->getContext()->getItemLabels();
        $table = $item->getTable();
        $outHeader = '';

        $headerLayout = (string)($record['header_layout'] ?? '');
        if ($headerLayout === '100') {
            $headerLayoutHiddenLabel = $this->getLanguageService()->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.6');
            $outHeader .= '<div class="element-preview-header-status">' . htmlspecialchars($headerLayoutHiddenLabel) . '</div>';
        }

        $date = (string)($record['date'] ?? '');
        if ($date !== '0' && $date !== '') {
            $dateLabel = $itemLabels['date'] . ' ' . BackendUtility::date($record['date']);
            $outHeader .= '<div class="element-preview-header-date">' . htmlspecialchars($dateLabel) . ' </div>';
        }

        $labelField = $GLOBALS['TCA'][$table]['ctrl']['label'] ?? '';
        $label = (string)($record[$labelField] ?? '');
        if ($label !== '') {
            $outHeader .= '<div class="element-preview-header-header">' . $this->linkEditContent($this->renderText($label), $record, $table) . '</div>';
        }

        $subHeader = (string)($record['subheader'] ?? '');
        if ($subHeader !== '') {
            $outHeader .= '<div class="element-preview-header-subheader">' . $this->linkEditContent($this->renderText($subHeader), $record) . '</div>';
        }

        return $outHeader;
    }

    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $recordType = $item->getRecordType();
        $languageService = $this->getLanguageService();
        $table = $item->getTable();
        $record = $item->getRecord();
        $out = '';

        // If record type is unknown, render warning message.
        if ($item->getTypeColumn() !== '' && !is_array($GLOBALS['TCA'][$table]['types'][$recordType] ?? null)) {
            $message = sprintf(
                $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'),
                $recordType
            );
            $out .= '<span class="badge badge-warning">' . htmlspecialchars($message) . '</span>';
            return $out;
        }

        // This preview should only be used for tt_content records.
        if ($table !== 'tt_content') {
            return $out;
        }

        // Draw preview of the item depending on its record type
        switch ($recordType) {
            case 'header':
                break;
            case 'uploads':
                if ($record['media']) {
                    $out .= $this->linkEditContent($this->getThumbCodeUnlinked($record, $table, 'media'), $record);
                }
                break;
            case 'shortcut':
                if (!empty($record['records'])) {
                    $shortcutContent = '';
                    $recordList = explode(',', $record['records']);
                    foreach ($recordList as $recordIdentifier) {
                        $split = BackendUtility::splitTable_Uid($recordIdentifier);
                        $shortcutTableName = empty($split[0]) ? $table : $split[0];
                        $shortcutRecord = BackendUtility::getRecord($shortcutTableName, $split[1]);
                        if (is_array($shortcutRecord)) {
                            $shortcutRecord = $this->translateShortcutRecord($record, $shortcutRecord, $shortcutTableName, (int)$split[1]);
                            $icon = $this->getIconFactory()->getIconForRecord($shortcutTableName, $shortcutRecord, IconSize::SMALL)->render();
                            $icon = BackendUtility::wrapClickMenuOnIcon(
                                $icon,
                                $shortcutTableName,
                                $shortcutRecord['uid'],
                                '1'
                            );
                            $shortcutContent .= '<li class="list-group-item">' . $icon . ' ' . htmlspecialchars(BackendUtility::getRecordTitle($shortcutTableName, $shortcutRecord)) . '</li>';
                        }
                    }
                    $out .= $shortcutContent ? '<ul class="list-group">' . $shortcutContent . '</ul>' : '';
                }
                break;
            case 'list':
                if (!empty($record['list_type'])) {
                    $label = BackendUtility::getLabelFromItemListMerged((int)$record['pid'], $table, 'list_type', $record['list_type'], $record);
                    if (!empty($label)) {
                        $out .= $this->linkEditContent('<strong>' . htmlspecialchars($languageService->sL($label)) . '</strong>', $record);
                    } else {
                        $message = sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingLabel'), $record['list_type']);
                        $out .= '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
                    }
                } else {
                    $out .= '<div class="alert alert-warning">' . htmlspecialchars($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:noPluginSelected')) . '</div>';
                }
                break;
            case 'menu_abstract':
            case 'menu_categorized_content':
            case 'menu_categorized_pages':
            case 'menu_pages':
            case 'menu_recently_updated':
            case 'menu_related_pages':
            case 'menu_section':
            case 'menu_section_pages':
            case 'menu_sitemap':
            case 'menu_sitemap_pages':
            case 'menu_subpages':
                if ($recordType !== 'menu_sitemap' && (($record['pages'] ?? false) || ($record['selected_categories'] ?? false))) {
                    // Show pages/categories if menu type is not "Sitemap"
                    $out .= $this->linkEditContent($this->generateListForMenuContentTypes($record, $recordType), $record);
                }
                break;
            default:
                if ($record['bodytext']) {
                    $out .= $this->linkEditContent($this->renderText($record['bodytext']), $record);
                }
                if ($record['image']) {
                    $out .= $this->linkEditContent($this->getThumbCodeUnlinked($record, $table, 'image'), $record);
                }
                if ($record['media']) {
                    $out .= $this->linkEditContent($this->getThumbCodeUnlinked($record, $table, 'media'), $record);
                }
                if ($record['assets']) {
                    $out .= $this->linkEditContent($this->getThumbCodeUnlinked($record, $table, 'assets'), $record);
                }
        }

        return $out;
    }

    /**
     * Render a footer for the record
     */
    public function renderPageModulePreviewFooter(GridColumnItem $item): string
    {
        $info = [];
        $record = $item->getRecord();
        $table = $item->getTable();
        $fieldList = [];
        $startTimeField = (string)($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['starttime'] ?? '');
        if ($startTimeField !== '') {
            $fieldList[] = $startTimeField;
        }
        $endTimeField = (string)($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime'] ?? '');
        if ($endTimeField !== '') {
            $fieldList[] = $endTimeField;
        }
        $feGroupField = (string)($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['fe_group'] ?? '');
        if ($feGroupField !== '') {
            $fieldList[] = $feGroupField;
        }
        if ($table === 'tt_content') {
            if (is_array($GLOBALS['TCA'][$table]['columns']['space_before_class'] ?? null)) {
                $fieldList[] = 'space_before_class';
            }
            if (is_array($GLOBALS['TCA'][$table]['columns']['space_after_class'] ?? null)) {
                $fieldList[] = 'space_after_class';
            }
        }
        if ($fieldList === []) {
            return '';
        }
        $this->getProcessedValue($item, $fieldList, $info);

        if (!empty($GLOBALS['TCA'][$table]['ctrl']['descriptionColumn']) && !empty($record[$GLOBALS['TCA'][$table]['ctrl']['descriptionColumn']])) {
            $info[] = htmlspecialchars($record[$GLOBALS['TCA'][$table]['ctrl']['descriptionColumn']]);
        }

        if ($info !== []) {
            return implode('<br>', $info);
        }
        return '';
    }

    public function wrapPageModulePreview(string $previewHeader, string $previewContent, GridColumnItem $item): string
    {
        $previewHeader = $previewHeader ? '<div class="element-preview-header">' . $previewHeader . '</div>' : '';
        $previewContent = $previewContent ? '<div class="element-preview-content">' . $previewContent . '</div>' : '';
        $preview = $previewHeader || $previewContent ? '<div class="element-preview">' . $previewHeader . $previewContent . '</div>' : '';

        return $preview;
    }

    protected function translateShortcutRecord(array $targetRecord, array $shortcutRecord, string $tableName, int $uid): array
    {
        $targetLanguage = (int)($targetRecord['sys_language_uid'] ?? 0);
        if ($targetLanguage === 0 || !BackendUtility::isTableLocalizable($tableName)) {
            return $shortcutRecord;
        }

        $languageField = $GLOBALS['TCA'][$tableName]['ctrl']['languageField'];
        $shortcutLanguage = (int)($shortcutRecord[$languageField] ?? 0);
        if ($targetLanguage === $shortcutLanguage) {
            return $shortcutRecord;
        }

        // record is localized - fetch the shortcut record translation, if available
        $shortcutRecordLocalization = BackendUtility::getRecordLocalization($tableName, $uid, $targetLanguage);
        if (is_array($shortcutRecordLocalization) && !empty($shortcutRecordLocalization)) {
            $shortcutRecord = $shortcutRecordLocalization[0];
        }

        return $shortcutRecord;
    }

    protected function getProcessedValue(GridColumnItem $item, string|array $fieldList, array &$info): void
    {
        $itemLabels = $item->getContext()->getItemLabels();
        $record = $item->getRecord();
        $table = $item->getTable();
        $fieldArr = is_array($fieldList) ? $fieldList : explode(',', $fieldList);
        foreach ($fieldArr as $field) {
            if ($record[$field]) {
                $fieldValue = BackendUtility::getProcessedValue($table, $field, $record[$field], 0, false, false, $record['uid'] ?? 0, true, $record['pid'] ?? 0) ?? '';
                $info[] = '<strong>' . htmlspecialchars((string)($itemLabels[$field] ?? '')) . '</strong> ' . htmlspecialchars($fieldValue);
            }
        }
    }

    /**
     * Create thumbnail code for record/field but not linked
     *
     * @param mixed[] $row Record array
     * @param string $table Table (record is from)
     * @param string $field Field name for which thumbnail are to be rendered.
     * @return string HTML for thumbnails, if any.
     */
    protected function getThumbCodeUnlinked(array $row, string $table, string $field): string
    {
        return BackendUtility::thumbCode(row: $row, table: $table, field: $field, linkInfoPopup: false);
    }

    /**
     * Processing of larger amounts of text (usually from RTE/bodytext fields) with word wrapping etc.
     *
     * @param string $input Input string
     * @return string Output string
     */
    protected function renderText(string $input): string
    {
        $input = strip_tags($input);
        $input = GeneralUtility::fixed_lgd_cs($input, 1500);
        return nl2br(htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8', false));
    }

    /**
     * Generates a list of selected pages or categories for the menu content types
     *
     * @param array $record row from pages
     */
    protected function generateListForMenuContentTypes(array $record, string $contentType): string
    {
        $table = 'pages';
        $field = 'pages';
        // get categories instead of pages
        if (str_contains($contentType, 'menu_categorized')) {
            $table = 'sys_category';
            $field = 'selected_categories';
        }
        if (trim($record[$field] ?? '') === '') {
            return '';
        }
        $content = '';
        $uidList = explode(',', $record[$field]);
        foreach ($uidList as $uid) {
            $uid = (int)$uid;
            $pageRecord = BackendUtility::getRecord($table, $uid, 'title');
            if ($pageRecord) {
                $content .= '<li class="list-group-item">' . htmlspecialchars($pageRecord['title']) . ' <span class="text-body-secondary">[' . $uid . ']</span></li>';
            }
        }
        return $content ? '<ul class="list-group">' . $content . '</ul>' : '';
    }

    /**
     * Will create a link on the input string and possibly a big button after the string which links to editing in the RTE.
     * Used for content element content displayed so the user can click the content / "Edit in Rich Text Editor" button
     *
     * @param string $linkText String to link. Must be prepared for HTML output.
     * @param array $row The row.
     * @return string If the whole thing was editable and $linkText is not empty $linkText is returned with link around. Otherwise just $linkText.
     */
    protected function linkEditContent(string $linkText, array $row, string $table = 'tt_content'): string
    {
        if (empty($linkText)) {
            return $linkText;
        }

        $backendUser = $this->getBackendUser();
        if ($backendUser->check('tables_modify', $table)
            && $backendUser->recordEditAccessInternals($table, $row)
            && (new Permission($backendUser->calcPerms(BackendUtility::getRecord('pages', $row['pid']) ?? [])))->editContentPermissionIsGranted()
        ) {
            $urlParameters = [
                'edit' => [
                    $table => [
                        $row['uid'] => 'edit',
                    ],
                ],
                'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri() . '#element-' . $table . '-' . $row['uid'],
            ];
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
            return '<a href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:edit')) . '">' . $linkText . '</a>';
        }
        return $linkText;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getIconFactory(): IconFactory
    {
        return GeneralUtility::makeInstance(IconFactory::class);
    }
}
