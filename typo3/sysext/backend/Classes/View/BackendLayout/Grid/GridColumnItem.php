<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Backend\View\BackendLayout\Grid;

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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Grid Column Item
 *
 * Model/proxy around a single record which appears in a grid column
 * in the page layout. Returns titles, urls etc. and performs basic
 * assertions on the contained content element record such as
 * is-versioned, is-editable, is-delible and so on.
 *
 * Accessed from Fluid templates.
 */
class GridColumnItem extends AbstractGridObject
{
    protected $record = [];

    /**
     * @var GridColumn
     */
    protected $column;

    public function __construct(BackendLayout $backendLayout, GridColumn $column, array $record)
    {
        parent::__construct($backendLayout);
        $this->column = $column;
        $this->record = $record;
        $backendLayout->getRecordRememberer()->rememberRecordUid($record['uid']);
        $backendLayout->getRecordRememberer()->rememberRecordUid($record['l18n_parent']);
    }

    public function isVersioned(): bool
    {
        return $this->record['_ORIG_uid'] > 0;
    }

    public function getPreview(): string
    {
        $item = $this;
        $row = $item->getRecord();
        $configuration = $this->backendLayout->getDrawingConfiguration();
        $out = '';
        $outHeader = '';

        if ($row['header']) {
            $hiddenHeaderNote = '';
            // If header layout is set to 'hidden', display an accordant note:
            if ($row['header_layout'] == 100) {
                $hiddenHeaderNote = ' <em>[' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.hidden')) . ']</em>';
            }
            $outHeader = $row['date']
                ? htmlspecialchars($configuration->getItemLabels()['date'] . ' ' . BackendUtility::date($row['date'])) . '<br />'
                : '';
            $outHeader .= '<strong>' . $this->linkEditContent($this->renderText($row['header']), $row)
                . $hiddenHeaderNote . '</strong><br />';
        }

        $drawItem = true;

        // Draw preview of the item depending on its CType (if not disabled by previous hook):
        if ($drawItem) {
            switch ($row['CType']) {
                case 'header':
                    if ($row['subheader']) {
                        $out .= $this->linkEditContent($this->renderText($row['subheader']), $row) . '<br />';
                    }
                    break;
                case 'bullets':
                case 'table':
                    if ($row['bodytext']) {
                        $out .= $this->linkEditContent($this->renderText($row['bodytext']), $row) . '<br />';
                    }
                    break;
                case 'uploads':
                    if ($row['media']) {
                        $out .= $this->linkEditContent($this->getThumbCodeUnlinked($row, 'tt_content', 'media'), $row) . '<br />';
                    }
                    break;
                case 'shortcut':
                    if (!empty($row['records'])) {
                        $shortcutContent = [];
                        $recordList = explode(',', $row['records']);
                        foreach ($recordList as $recordIdentifier) {
                            $split = BackendUtility::splitTable_Uid($recordIdentifier);
                            $tableName = empty($split[0]) ? 'tt_content' : $split[0];
                            $shortcutRecord = BackendUtility::getRecord($tableName, $split[1]);
                            if (is_array($shortcutRecord)) {
                                $icon = $this->iconFactory->getIconForRecord($tableName, $shortcutRecord, Icon::SIZE_SMALL)->render();
                                $icon = BackendUtility::wrapClickMenuOnIcon(
                                    $icon,
                                    $tableName,
                                    $shortcutRecord['uid']
                                );
                                $shortcutContent[] = $icon
                                    . htmlspecialchars(BackendUtility::getRecordTitle($tableName, $shortcutRecord));
                            }
                        }
                        $out .= implode('<br />', $shortcutContent) . '<br />';
                    }
                    break;
                case 'list':
                    $hookOut = '';
                    $_params = ['pObj' => &$this, 'row' => $row, 'infoArr' => []];
                    foreach (
                        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$row['list_type']] ??
                        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['_DEFAULT'] ??
                        [] as $_funcRef
                    ) {
                        $hookOut .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                    }
                    if ((string)$hookOut !== '') {
                        $out .= $hookOut;
                    } elseif (!empty($row['list_type'])) {
                        $label = BackendUtility::getLabelFromItemListMerged($row['pid'], 'tt_content', 'list_type', $row['list_type']);
                        if (!empty($label)) {
                            $out .= $this->linkEditContent('<strong>' . htmlspecialchars($this->getLanguageService()->sL($label)) . '</strong>', $row) . '<br />';
                        } else {
                            $message = sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'), $row['list_type']);
                            $out .= '<span class="label label-warning">' . htmlspecialchars($message) . '</span>';
                        }
                    } else {
                        $out .= '<strong>' . $this->getLanguageService()->getLL('noPluginSelected') . '</strong>';
                    }
                    $out .= htmlspecialchars($this->getLanguageService()->sL(
                        BackendUtility::getLabelFromItemlist('tt_content', 'pages', $row['pages'])
                    )) . '<br />';
                    break;
                default:
                    $contentType = $this->backendLayout->getDrawingConfiguration()->getContentTypeLabels()[$row['CType']];
                    if (!isset($contentType)) {
                        $contentType =  BackendUtility::getLabelFromItemListMerged($row['pid'], 'tt_content', 'CType', $row['CType']);
                    }

                    if ($contentType) {
                        $out .= $this->linkEditContent('<strong>' . htmlspecialchars($contentType) . '</strong>', $row) . '<br />';
                        if ($row['bodytext']) {
                            $out .= $this->linkEditContent($this->renderText($row['bodytext']), $row) . '<br />';
                        }
                        if ($row['image']) {
                            $out .= $this->linkEditContent($this->getThumbCodeUnlinked($row, 'tt_content', 'image'), $row) . '<br />';
                        }
                    } else {
                        $message = sprintf(
                            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'),
                            $row['CType']
                        );
                        $out .= '<span class="label label-warning">' . htmlspecialchars($message) . '</span>';
                    }
            }
        }
        $out = '<span class="exampleContent">' . $out . '</span>';
        $out = $outHeader . $out;
        if ($item->isDisabled()) {
            return '<span class="text-muted">' . $out . '</span>';
        }
        return $out;
    }

    public function getWrapperClassName(): string
    {
        $wrapperClassNames = [];
        if ($this->isDisabled()) {
            $wrapperClassNames[] = 't3-page-ce-hidden t3js-hidden-record';
        } elseif (!in_array($this->record['colPos'], $this->backendLayout->getColumnPositionNumbers())) {
            $wrapperClassNames[] = 't3-page-ce-warning';
        }

        return implode(' ', $wrapperClassNames);
    }

    public function isDelible(): bool
    {
        $backendUser = $this->getBackendUser();
        if (!$backendUser->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT)) {
            return false;
        }
        return !(bool)($backendUser->getTSConfig()['options.']['disableDelete.']['tt_content'] ?? $backendUser->getTSConfig()['options.']['disableDelete'] ?? false);
    }

    public function getDeleteUrl(): string
    {
        $params = '&cmd[tt_content][' . $this->record['uid'] . '][delete]=1';
        return BackendUtility::getLinkToDataHandlerAction($params);
    }

    public function getDeleteTitle(): string
    {
        return $this->getLanguageService()->getLL('deleteItem');
    }

    public function getDeleteConfirmText(): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:label.confirm.delete_record.title');
    }

    public function getDeleteCancelText(): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:cancel');
    }

    public function getFooterInfo(): iterable
    {
        $info = [];
        $this->getProcessedValue('starttime,endtime,fe_group,space_before_class,space_after_class', $info);

        if (!empty($GLOBALS['TCA']['tt_content']['ctrl']['descriptionColumn']) && !empty($this->record[$GLOBALS['TCA']['tt_content']['ctrl']['descriptionColumn']])) {
            $info[] = $this->record[$GLOBALS['TCA']['tt_content']['ctrl']['descriptionColumn']];
        }

        return $info;
    }

    /**
     * Renders the language flag and language title, but only if an icon is given, otherwise just the language
     *
     * @param SiteLanguage $language
     * @return string
     */
    protected function renderLanguageFlag(SiteLanguage $language)
    {
        $title = htmlspecialchars($language->getTitle());
        if ($language->getFlagIdentifier()) {
            $icon = $this->iconFactory->getIcon(
                $language->getFlagIdentifier(),
                Icon::SIZE_SMALL
            )->render();
            return '<span title="' . $title . '">' . $icon . '</span>&nbsp;' . $title;
        }
        return $title;
    }

    /**
     * Create thumbnail code for record/field but not linked
     *
     * @param mixed[] $row Record array
     * @param string $table Table (record is from)
     * @param string $field Field name for which thumbnail are to be rendered.
     * @return string HTML for thumbnails, if any.
     */
    protected function getThumbCodeUnlinked($row, $table, $field)
    {
        return BackendUtility::thumbCode($row, $table, $field, '', '', null, 0, '', '', false);
    }

    /**
     * Will create a link on the input string and possibly a big button after the string which links to editing in the RTE.
     * Used for content element content displayed so the user can click the content / "Edit in Rich Text Editor" button
     *
     * @param string $str String to link. Must be prepared for HTML output.
     * @param array $row The row.
     * @return string If the whole thing was editable $str is return with link around. Otherwise just $str.
     */
    public function linkEditContent($str, $row)
    {
        if ($this->getBackendUser()->recordEditAccessInternals('tt_content', $row)) {
            $urlParameters = [
                'edit' => [
                    'tt_content' => [
                        $row['uid'] => 'edit'
                    ]
                ],
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI') . '#element-tt_content-' . $row['uid']
            ];
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
            return '<a href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('edit')) . '">' . $str . '</a>';
        }
        return $str;
    }

    /**
     * Processing of larger amounts of text (usually from RTE/bodytext fields) with word wrapping etc.
     *
     * @param string $input Input string
     * @return string Output string
     */
    public function renderText($input): string
    {
        $input = strip_tags($input);
        $input = GeneralUtility::fixed_lgd_cs($input, 1500);
        return nl2br(htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8', false));
    }

    protected function getProcessedValue(string $fieldList, array &$info): void
    {
        $itemLabels = $this->backendLayout->getDrawingConfiguration()->getItemLabels();
        $fieldArr = explode(',', $fieldList);
        foreach ($fieldArr as $field) {
            if ($this->record[$field]) {
                $info[] = '<strong>' . htmlspecialchars($itemLabels[$field]) . '</strong> '
                    . htmlspecialchars(BackendUtility::getProcessedValue('tt_content', $field, $this->record[$field]));
            }
        }
    }

    public function getIcons(): string
    {
        $table = 'tt_content';
        $row = $this->record;
        $icons = [];

        if ($this->getBackendUser()->recordEditAccessInternals($table, $row)) {
            $toolTip = BackendUtility::getRecordToolTip($row, $table);
            $icon = '<span ' . $toolTip . '>' . $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render() . '</span>';
            $icons[] = BackendUtility::wrapClickMenuOnIcon($icon, $table, $row['uid']);
        }
        $icons[] = $this->renderLanguageFlag($this->backendLayout->getDrawingConfiguration()->getSiteLanguage((int)$row['sys_language_uid']));

        if ($lockInfo = BackendUtility::isRecordLocked('tt_content', $row['uid'])) {
            $icons[] = '<a href="#" data-toggle="tooltip" data-title="' . htmlspecialchars($lockInfo['msg']) . '">'
                . $this->iconFactory->getIcon('warning-in-use', Icon::SIZE_SMALL)->render() . '</a>';
        }

        $_params = ['tt_content', $row['uid'], &$row];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] ?? [] as $_funcRef) {
            $icons[] = GeneralUtility::callUserFunction($_funcRef, $_params, $this);
        }
        return implode(' ', $icons);
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function getColumn(): GridColumn
    {
        return $this->column;
    }

    public function isDisabled(): bool
    {
        $table = 'tt_content';
        $row = $this->getRecord();
        $enableCols = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'];
        return $enableCols['disabled'] && $row[$enableCols['disabled']]
            || $enableCols['starttime'] && $row[$enableCols['starttime']] > $GLOBALS['EXEC_TIME']
            || $enableCols['endtime'] && $row[$enableCols['endtime']] && $row[$enableCols['endtime']] < $GLOBALS['EXEC_TIME'];
    }

    public function hasTranslation(): bool
    {
        $contentElements = $this->column->getRecords();
        $id = $this->backendLayout->getDrawingConfiguration()->getPageId();
        $language = $this->backendLayout->getDrawingConfiguration()->getLanguageColumnsPointer();
        // If in default language, you may always create new entries
        // Also, you may override this strict behavior via user TS Config
        // If you do so, you're on your own and cannot rely on any support by the TYPO3 core.
        $allowInconsistentLanguageHandling = (bool)(BackendUtility::getPagesTSconfig($id)['mod.']['web_layout.']['allowInconsistentLanguageHandling'] ?? false);
        if ($language === 0 || $allowInconsistentLanguageHandling) {
            return false;
        }

        return $this->backendLayout->getContentFetcher()->getTranslationData($contentElements, $language)['hasTranslations'] ?? false;
    }

    public function isDeletePlaceholder(): bool
    {
        return VersionState::cast($this->record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER);
    }

    public function isEditable(): bool
    {
        $languageId = $this->backendLayout->getDrawingConfiguration()->getLanguageColumnsPointer();
        if ($this->getBackendUser()->isAdmin()) {
            return true;
        }
        $pageRecord = $this->backendLayout->getDrawingConfiguration()->getPageRecord();
        return !$pageRecord['editlock']
            && $this->getBackendUser()->doesUserHaveAccess($pageRecord, Permission::CONTENT_EDIT)
            && ($languageId === null || $this->getBackendUser()->checkLanguageAccess($languageId));
    }

    public function isDragAndDropAllowed(): bool
    {
        $pageRecord = $this->backendLayout->getDrawingConfiguration()->getPageRecord();
        return (int)$this->record['l18n_parent'] === 0 &&
            (
                $this->getBackendUser()->isAdmin()
                || ((int)$this->record['editlock'] === 0 && (int)$pageRecord['editlock'] === 0)
                && $this->getBackendUser()->doesUserHaveAccess($pageRecord, Permission::CONTENT_EDIT)
                && $this->getBackendUser()->checkAuthMode('tt_content', 'CType', $this->record['CType'], $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'])
            )
        ;
    }

    public function getNewContentAfterLinkTitle(): string
    {
        return $this->getLanguageService()->getLL('newContentElement');
    }

    public function getNewContentAfterTitle(): string
    {
        return $this->getLanguageService()->getLL('content');
    }

    public function getNewContentAfterUrl(): string
    {
        $pageId = $this->backendLayout->getDrawingConfiguration()->getPageId();
        $urlParameters = [
            'id' => $pageId,
            'sys_language_uid' => $this->backendLayout->getDrawingConfiguration()->getLanguageColumnsPointer(),
            'colPos' => $this->column->getColumnNumber(),
            'uid_pid' => -$this->record['uid'],
            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
        ];
        $routeName = BackendUtility::getPagesTSconfig($pageId)['mod.']['newContentElementWizard.']['override']
            ?? 'new_content_element_wizard';
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute($routeName, $urlParameters);
    }

    public function getVisibilityToggleUrl(): string
    {
        $hiddenField = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'];
        if ($this->record[$hiddenField]) {
            $value = 0;
        } else {
            $value = 1;
        }
        $params = '&data[tt_content][' . ($this->record['_ORIG_uid'] ?: $this->record['uid'])
            . '][' . $hiddenField . ']=' . $value;
        return BackendUtility::getLinkToDataHandlerAction($params) . '#element-tt_content-' . $this->record['uid'];
    }

    public function getVisibilityToggleTitle(): string
    {
        $hiddenField = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'];
        return $this->getLanguageService()->getLL($this->record[$hiddenField] ? 'unhide' : 'hide');
    }

    public function getVisibilityToggleIconName(): string
    {
        $hiddenField = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'];
        return $this->record[$hiddenField] ? 'unhide' : 'hide';
    }

    public function isVisibilityToggling(): bool
    {
        $hiddenField = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'];
        return $hiddenField && $GLOBALS['TCA']['tt_content']['columns'][$hiddenField]
            && (
                !$GLOBALS['TCA']['tt_content']['columns'][$hiddenField]['exclude']
                || $this->getBackendUser()->check('non_exclude_fields', 'tt_content:' . $hiddenField)
            )
        ;
    }

    public function getEditUrl(): string
    {
        $urlParameters = [
            'edit' => [
                'tt_content' => [
                    $this->record['uid'] => 'edit',
                ]
            ],
            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI') . '#element-tt_content-' . $this->record['uid'],
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters) . '#element-tt_content-' . $this->record['uid'];
    }
}
