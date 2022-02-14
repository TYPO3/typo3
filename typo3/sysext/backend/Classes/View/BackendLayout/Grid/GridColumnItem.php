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

namespace TYPO3\CMS\Backend\View\BackendLayout\Grid;

use TYPO3\CMS\Backend\Preview\StandardPreviewRendererResolver;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Grid Column Item
 *
 * Model/proxy around a single record which appears in a grid column
 * in the page layout. Returns titles, urls etc. and performs basic
 * assertions on the contained content element record such as
 * is-versioned, is-editable, is-delible and so on.
 *
 * Accessed from Fluid templates.
 *
 * @internal this is experimental and subject to change in TYPO3 v10 / v11
 */
class GridColumnItem extends AbstractGridObject
{
    /**
     * @var mixed[]
     */
    protected $record = [];

    /**
     * @var GridColumn
     */
    protected $column;

    /**
     * @var GridColumnItem[]
     */
    protected $translations = [];

    public function __construct(PageLayoutContext $context, GridColumn $column, array $record)
    {
        parent::__construct($context);
        $this->column = $column;
        $this->record = $record;
    }

    public function isVersioned(): bool
    {
        return ($this->record['_ORIG_uid'] ?? 0) > 0 || (int)($this->record['t3ver_state'] ?? 0) !== 0;
    }

    public function getPreview(): string
    {
        $record = $this->getRecord();
        $previewRenderer = GeneralUtility::makeInstance(StandardPreviewRendererResolver::class)
            ->resolveRendererFor(
                'tt_content',
                $record,
                $this->context->getPageId()
            );
        $previewHeader = $previewRenderer->renderPageModulePreviewHeader($this);

        $drawItem = true;
        $previewContent = '';
        // Hook: Render an own preview of a record
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'])) {
            $pageLayoutView = PageLayoutView::createFromPageLayoutContext($this->getContext());
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'] ?? [] as $className) {
                $hookObject = GeneralUtility::makeInstance($className);
                if (!$hookObject instanceof PageLayoutViewDrawItemHookInterface) {
                    throw new \UnexpectedValueException($className . ' must implement interface ' . PageLayoutViewDrawItemHookInterface::class, 1582574553);
                }
                $hookObject->preProcess($pageLayoutView, $drawItem, $previewHeader, $previewContent, $record);
            }
            $this->setRecord($record);
        }

        if ($drawItem) {
            $previewContent = $previewRenderer->renderPageModulePreviewContent($this);
        }

        return $previewRenderer->wrapPageModulePreview($previewHeader, $previewContent, $this);
    }

    public function getWrapperClassName(): string
    {
        $wrapperClassNames = [];
        if ($this->isDisabled()) {
            $wrapperClassNames[] = 't3-page-ce-hidden t3js-hidden-record';
        }
        if ($this->isInconsistentLanguage()) {
            $wrapperClassNames[] = 't3-page-ce-warning';
        }

        return implode(' ', $wrapperClassNames);
    }

    public function isDelible(): bool
    {
        $backendUser = $this->getBackendUser();
        if (!$backendUser->doesUserHaveAccess($this->context->getPageRecord(), Permission::CONTENT_EDIT)) {
            return false;
        }
        return !(bool)($backendUser->getTSConfig()['options.']['disableDelete.']['tt_content'] ?? $backendUser->getTSConfig()['options.']['disableDelete'] ?? false);
    }

    public function getDeleteUrl(): string
    {
        $params = '&cmd[tt_content][' . $this->record['uid'] . '][delete]=1';
        return BackendUtility::getLinkToDataHandlerAction($params);
    }

    public function getFooterInfo(): string
    {
        $record = $this->getRecord();
        $previewRenderer = GeneralUtility::makeInstance(StandardPreviewRendererResolver::class)
            ->resolveRendererFor(
                'tt_content',
                $record,
                $this->context->getPageId()
            );
        return $previewRenderer->renderPageModulePreviewFooter($this);
    }

    public function getIcons(): string
    {
        $table = 'tt_content';
        $row = $this->record;
        $icons = [];

        $toolTip = BackendUtility::getRecordToolTip($row, $table);
        $icon = '<span ' . $toolTip . '>' . $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render() . '</span>';
        if ($this->getBackendUser()->recordEditAccessInternals($table, $row)) {
            $icon = BackendUtility::wrapClickMenuOnIcon($icon, $table, $row['uid']);
        }
        $icons[] = $icon;

        if ($lockInfo = BackendUtility::isRecordLocked('tt_content', $row['uid'])) {
            $icons[] = '<a href="#" data-bs-toggle="tooltip" title="' . htmlspecialchars($lockInfo['msg']) . '">'
                . $this->iconFactory->getIcon('warning-in-use', Icon::SIZE_SMALL)->render() . '</a>';
        }

        $_params = ['tt_content', $row['uid'], &$row];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] ?? [] as $_funcRef) {
            $icons[] = GeneralUtility::callUserFunction($_funcRef, $_params, $this);
        }
        return implode(' ', $icons);
    }

    public function getSiteLanguage(): SiteLanguage
    {
        return $this->context->getSiteLanguage((int)$this->getRecord()['sys_language_uid']);
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function setRecord(array $record): void
    {
        $this->record = $record;
    }

    public function getColumn(): GridColumn
    {
        return $this->column;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function addTranslation(int $languageId, GridColumnItem $translation): GridColumnItem
    {
        $this->translations[$languageId] = $translation;
        return $this;
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

    public function isEditable(): bool
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser->isAdmin()) {
            return true;
        }
        $pageRecord = $this->context->getPageRecord();
        return !(bool)($pageRecord['editlock'] ?? false)
            && $backendUser->doesUserHaveAccess($pageRecord, Permission::CONTENT_EDIT)
            && $backendUser->recordEditAccessInternals('tt_content', $this->record);
    }

    public function isDragAndDropAllowed(): bool
    {
        $pageRecord = $this->context->getPageRecord();
        return (int)$this->record['l18n_parent'] === 0 &&
            (
                $this->getBackendUser()->isAdmin()
                || ((int)$this->record['editlock'] === 0 && (int)$pageRecord['editlock'] === 0)
                && $this->getBackendUser()->doesUserHaveAccess($pageRecord, Permission::CONTENT_EDIT)
                && $this->getBackendUser()->checkAuthMode('tt_content', 'CType', $this->record['CType'], $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'])
            )
        ;
    }

    protected function isInconsistentLanguage(): bool
    {
        $allowInconsistentLanguageHandling = (bool)(BackendUtility::getPagesTSconfig($this->getContext()->getPageId())['mod.']['web_layout.']['allowInconsistentLanguageHandling'] ?? false);
        return !$allowInconsistentLanguageHandling
            && $this->getSiteLanguage()->getLanguageId() !== 0
            && $this->getContext()->getLanguageModeIdentifier() === 'mixed'
            && (int)$this->record['l18n_parent'] === 0;
    }

    public function getNewContentAfterUrl(): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $pageId = $this->context->getPageId();

        if ($this->context->getDrawingConfiguration()->getShowNewContentWizard()) {
            $urlParameters = [
                'id' => $pageId,
                'sys_language_uid' => $this->context->getSiteLanguage()->getLanguageId(),
                'colPos' => $this->column->getColumnNumber(),
                'uid_pid' => -$this->record['uid'],
                'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
            ];
            $routeName = BackendUtility::getPagesTSconfig($pageId)['mod.']['newContentElementWizard.']['override']
                ?? 'new_content_element_wizard';
        } else {
            $urlParameters = [
                'edit' => [
                    'tt_content' => [
                        -$this->record['uid'] => 'new',
                    ],
                ],
                'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
            ];
            $routeName = 'record_edit';
        }

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
        $params = '&data[tt_content][' . (($this->record['_ORIG_uid'] ?? false) ?: ($this->record['uid'] ?? 0))
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
                !($GLOBALS['TCA']['tt_content']['columns'][$hiddenField]['exclude'] ?? false)
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
                ],
            ],
            'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri() . '#element-tt_content-' . $this->record['uid'],
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters) . '#element-tt_content-' . $this->record['uid'];
    }
}
