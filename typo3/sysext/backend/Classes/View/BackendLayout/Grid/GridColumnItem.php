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

use TYPO3\CMS\Backend\Preview\StandardPreviewRendererResolver;
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
        $backendLayout->getRecordRememberer()->rememberRecordUid((int)$record['uid']);
        $backendLayout->getRecordRememberer()->rememberRecordUid((int)$record['l18n_parent']);
    }

    public function isVersioned(): bool
    {
        return $this->record['_ORIG_uid'] > 0;
    }

    public function getPreview(): string
    {
        $record = $this->getRecord();
        $previewRenderer = GeneralUtility::makeInstance(StandardPreviewRendererResolver::class)
            ->resolveRendererFor(
                'tt_content',
                $record,
                $this->backendLayout->getDrawingConfiguration()->getPageId()
            );
        $previewHeader = $previewRenderer->renderPageModulePreviewHeader($this);
        $previewContent = $previewRenderer->renderPageModulePreviewContent($this);
        return $previewRenderer->wrapPageModulePreview($previewHeader, $previewContent, $this);
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
        if (!$backendUser->doesUserHaveAccess($this->backendLayout->getDrawingConfiguration()->getPageRecord(), Permission::CONTENT_EDIT)) {
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

    public function getFooterInfo(): string
    {
        $record = $this->getRecord();
        $previewRenderer = GeneralUtility::makeInstance(StandardPreviewRendererResolver::class)
            ->resolveRendererFor(
                'tt_content',
                $record,
                $this->backendLayout->getDrawingConfiguration()->getPageId()
            );
        return $previewRenderer->renderPageModulePreviewFooter($this);
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
