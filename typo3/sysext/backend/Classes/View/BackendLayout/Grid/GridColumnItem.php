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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Preview\StandardPreviewRendererResolver;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Database\ReferenceIndex;
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
        $previewRenderer = GeneralUtility::makeInstance(StandardPreviewRendererResolver::class)
            ->resolveRendererFor(
                'tt_content',
                $this->record,
                $this->context->getPageId()
            );
        $previewHeader = $previewRenderer->renderPageModulePreviewHeader($this);

        // Dispatch event to allow listeners adding an alternative content type
        // specific preview or to manipulate the content elements' record data.
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
            new PageContentPreviewRenderingEvent('tt_content', $this->record, $this->context)
        );

        // Update the modified record data
        $this->record = $event->getRecord();

        // Get specific preview from listeners. In case non was added,
        // fall back to the standard preview rendering workflow.
        $previewContent = $event->getPreviewContent();
        if ($previewContent === null) {
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
        return (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
            'tce_db',
            [
                'cmd' => [
                    'tt_content' => [
                        $this->record['uid'] => [
                            'delete' => 1,
                        ],
                    ],
                ],
                'redirect' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
            ]
        );
    }

    public function getDeleteMessage(): string
    {
        $recordInfo = $this->record['header'] ?? '';
        if ($this->getBackendUser()->shallDisplayDebugInformation()) {
            $recordInfo .= ' [tt:content:' . $this->record['uid'] . ']';
        }

        $refCountMsg = BackendUtility::referenceCount(
            'tt_content',
            $this->record['uid'],
            LF . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToRecord'),
            (string)$this->getReferenceCount($this->record['uid'])
        ) . BackendUtility::translationCount(
            'tt_content',
            $this->record['uid'],
            LF . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.translationsOfRecord')
        );

        return sprintf($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:deleteWarning'), trim($recordInfo)) . $refCountMsg;
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

    public function getContentTypeLabel(): string
    {
        $contentTypeLabels = $this->context->getContentTypeLabels();
        $ctype = $this->record['CType'] ?? '';
        return $contentTypeLabels[$ctype] ??
            BackendUtility::getLabelFromItemListMerged((int)$this->record['pid'], 'tt_content', 'CType', $ctype);
    }

    public function getIcons(): string
    {
        $table = 'tt_content';
        $row = $this->record;
        $icons = [];

        $icon = $this->iconFactory
            ->getIconForRecord($table, $row, Icon::SIZE_SMALL)
            ->setTitle(BackendUtility::getRecordIconAltText($row, $table))
            ->render();
        if ($this->getBackendUser()->recordEditAccessInternals($table, $row)) {
            $icon = BackendUtility::wrapClickMenuOnIcon($icon, $table, $row['uid']);
        }
        $icons[] = $icon;

        if ($lockInfo = BackendUtility::isRecordLocked('tt_content', $row['uid'])) {
            $icons[] = '<a href="#" title="' . htmlspecialchars($lockInfo['msg']) . '">'
                . $this->iconFactory->getIcon('status-user-backend', Icon::SIZE_SMALL, 'overlay-edit')->render() . '</a>';
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
                && $this->getBackendUser()->checkAuthMode('tt_content', 'CType', $this->record['CType'])
            )
        ;
    }

    public function isInconsistentLanguage(): bool
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

        return (string)$uriBuilder->buildUriFromRoute('new_content_element_wizard', [
            'id' => $pageId,
            'sys_language_uid' => $this->context->getSiteLanguage()->getLanguageId(),
            'colPos' => $this->column->getColumnNumber(),
            'uid_pid' => -$this->record['uid'],
            'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
        ]);
    }

    public function getVisibilityToggleUrl(): string
    {
        $hiddenField = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'];
        if ($this->record[$hiddenField]) {
            $value = 0;
        } else {
            $value = 1;
        }
        return GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
            'tce_db',
            [
                'data' => [
                    'tt_content' => [
                        (($this->record['_ORIG_uid'] ?? false) ?: ($this->record['uid'] ?? 0)) => [
                            $hiddenField => $value,
                        ],
                    ],
                ],
                'redirect' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
            ]
        ) . '#element-tt_content-' . $this->record['uid'];
    }

    public function getVisibilityToggleTitle(): string
    {
        $hiddenField = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'];
        if ($this->record[$hiddenField]) {
            return $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:unhide');
        }
        return $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:hide');
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

    /**
     * Gets the number of records referencing the record with the UID $uid in
     * the table tt_content.
     *
     * @return int The number of references to record $uid in table
     */
    protected function getReferenceCount(int $uid): int
    {
        return GeneralUtility::makeInstance(ReferenceIndex::class)->getNumberOfReferencedRecords('tt_content', $uid);
    }
}
