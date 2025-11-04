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
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
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
 * @internal
 */
class GridColumnItem extends AbstractGridObject
{
    /**
     * @var GridColumnItem[]
     */
    protected array $translations = [];
    protected TcaSchema $schema;

    public function __construct(
        PageLayoutContext $context,
        protected readonly GridColumn $column,
        protected RecordInterface $record,
        protected readonly string $table = 'tt_content'
    ) {
        parent::__construct($context);
        $this->schema = GeneralUtility::makeInstance(TcaSchemaFactory::class)->get($this->table);
    }

    public function isVersioned(): bool
    {
        return $this->record->getComputedProperties()->getVersionedUid() > 0 || (int)($this->getRow()['t3ver_state'] ?? 0) !== 0;
    }

    public function getPreview(): string
    {
        $previewRenderer = GeneralUtility::makeInstance(StandardPreviewRendererResolver::class)->resolveRendererFor($this->record);
        $previewHeader = $previewRenderer->renderPageModulePreviewHeader($this);

        // Dispatch event to allow listeners adding an alternative content type
        // specific preview or to manipulate the content elements' record data.
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
            new PageContentPreviewRenderingEvent($this->table, $this->getRecordType(), $this->record, $this->context)
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
        return !($backendUser->getTSConfig()['options.']['disableDelete.'][$this->table] ?? $backendUser->getTSConfig()['options.']['disableDelete'] ?? false);
    }

    public function getDeleteUrl(): string
    {
        return (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
            'tce_db',
            [
                'cmd' => [
                    $this->table => [
                        $this->record->getUid() => [
                            'delete' => 1,
                        ],
                    ],
                ],
                'redirect' => $this->context->getCurrentRequest()->getAttribute('normalizedParams')->getRequestUri(),
            ]
        );
    }

    public function getDeleteMessage(): string
    {
        $recordInfo = GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle($this->table, $this->getRow()), (int)$this->getBackendUser()->uc['titleLen']);
        if ($this->getBackendUser()->shallDisplayDebugInformation()) {
            $recordInfo .= ' [' . $this->table . ':' . $this->record->getUid() . ']';
        }

        $refCountMsg = BackendUtility::referenceCount(
            $this->table,
            $this->record->getUid(),
            LF . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToRecord'),
            (string)$this->getReferenceCount($this->record->getUid())
        ) . BackendUtility::translationCount(
            $this->table,
            (string)$this->record->getUid(),
            LF . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.translationsOfRecord')
        );

        return sprintf($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:deleteWarning'), trim($recordInfo)) . $refCountMsg;
    }

    public function getFooterInfo(): string
    {
        $previewRenderer = GeneralUtility::makeInstance(StandardPreviewRendererResolver::class)->resolveRendererFor($this->record);
        return $previewRenderer->renderPageModulePreviewFooter($this);
    }

    public function getContentTypeLabel(): string
    {
        if (($recordType = $this->getRecordType()) === '') {
            return '';
        }
        $contentTypeLabels = $this->context->getContentTypeLabels();
        $contentTypeLabel = $contentTypeLabels[$recordType] ?? '';
        if ($contentTypeLabel === '') {
            $contentTypeLabel = $this->getLabelFromItemListMerged();
            $contentTypeLabel = $this->getLanguageService()->sL($contentTypeLabel);
        }
        return $contentTypeLabel;
    }

    public function getIcons(): string
    {
        $row = $this->record->getRawRecord()?->toArray() ?? [];
        $icons = [];

        $icon = $this->iconFactory
            ->getIconForRecord($this->table, $row, IconSize::SMALL)
            ->setTitle(BackendUtility::getRecordIconAltText($row, $this->table, false))
            ->render();
        if ($this->getBackendUser()->recordEditAccessInternals($this->table, $this->getRow())) {
            $icon = BackendUtility::wrapClickMenuOnIcon($icon, $this->table, $this->record->getUid());
        }
        $icons[] = $icon;

        if ($lockInfo = BackendUtility::isRecordLocked($this->table, $this->record->getUid())) {
            $icons[] = '<a href="#" title="' . htmlspecialchars($lockInfo['msg']) . '">'
                . $this->iconFactory->getIcon('status-user-backend', IconSize::SMALL, 'overlay-edit')->render() . '</a>';
        }
        return implode(' ', $icons);
    }

    public function getSiteLanguage(): SiteLanguage
    {
        return $this->context->getSiteLanguage((int)($this->getRow()[$this->schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName()] ?? 0));
    }

    public function getRecord(): RecordInterface
    {
        return $this->record;
    }

    public function getRow(): array
    {
        return $this->record->getRawRecord()?->toArray(true) ?? [];
    }

    public function setRecord(RecordInterface $record): void
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
        return ($this->schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField) && $this->record->has((string)$this->schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)) && $this->record->get((string)$this->schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)))
                || ($this->schema->hasCapability(TcaSchemaCapability::RestrictionStartTime) && $this->record->has((string)$this->schema->getCapability(TcaSchemaCapability::RestrictionStartTime)) && $this->record->get((string)$this->schema->getCapability(TcaSchemaCapability::RestrictionStartTime)) > $GLOBALS['EXEC_TIME'])
                || ($this->schema->hasCapability(TcaSchemaCapability::RestrictionEndTime) && $this->record->has((string)$this->schema->getCapability(TcaSchemaCapability::RestrictionEndTime)) && $this->record->get((string)$this->schema->getCapability(TcaSchemaCapability::RestrictionEndTime)) < $GLOBALS['EXEC_TIME']);
    }

    public function isEditable(): bool
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser->isAdmin()) {
            return true;
        }
        $pageRecord = $this->context->getPageRecord();
        return $backendUser->doesUserHaveAccess($pageRecord, Permission::CONTENT_EDIT)
            && $backendUser->recordEditAccessInternals($this->table, $this->record)
            && (
                !($pagesSchema = GeneralUtility::makeInstance(TcaSchemaFactory::class)->get('pages'))->hasCapability(TcaSchemaCapability::EditLock)
                || !($pageRecord[$pagesSchema->getCapability(TcaSchemaCapability::EditLock)->getFieldName()] ?? false)
            );
    }

    public function isDragAndDropAllowed(): bool
    {
        $pageRecord = $this->context->getPageRecord();
        $typeColumn = $this->getTypeColumn();
        return (int)($this->getRow()[$this->schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName()] ?? 0) === 0
            && (
                $this->getBackendUser()->isAdmin()
                || (
                    (
                        !($this->getRow()[$this->schema->getCapability(TcaSchemaCapability::EditLock)->getFieldName()] ?? false)
                        && (
                            !($pagesSchema = GeneralUtility::makeInstance(TcaSchemaFactory::class)->get('pages'))->hasCapability(TcaSchemaCapability::EditLock)
                            || !($pageRecord[$pagesSchema->getCapability(TcaSchemaCapability::EditLock)->getFieldName()] ?? false)
                        )
                    )
                    && $this->getBackendUser()->doesUserHaveAccess($pageRecord, Permission::CONTENT_EDIT)
                    && $this->getBackendUser()->checkAuthMode($this->table, $typeColumn, $this->getRecordType())
                )
            )
        ;
    }

    public function isInconsistentLanguage(): bool
    {
        $allowInconsistentLanguageHandling = $this->context->getDrawingConfiguration()->getAllowInconsistentLanguageHandling();
        return !$allowInconsistentLanguageHandling
            && $this->getSiteLanguage()->getLanguageId() !== 0
            && $this->context->getLanguageModeIdentifier() === 'mixed'
            && (int)($this->getRow()[$this->schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName()] ?? 0) === 0;
    }

    public function getNewContentAfterUrl(): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('new_content_element_wizard', [
            'id' => $this->context->getPageId(),
            'sys_language_uid' => $this->context->getSiteLanguage()->getLanguageId(),
            'colPos' => $this->column->getColumnNumber(),
            'uid_pid' => -$this->record->getUid(),
            'returnUrl' => $this->context->getCurrentRequest()->getAttribute('normalizedParams')->getRequestUri(),
        ]);
    }

    public function getVisibilityToggleUrl(): string
    {
        $disabledFieldName = $this->getDisabledFieldName();
        if ($this->getRow()[$disabledFieldName] ?? false) {
            $value = 0;
        } else {
            $value = 1;
        }
        return GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
            'tce_db',
            [
                'data' => [
                    $this->table => [
                        $this->record->getComputedProperties()->getVersionedUid() ?: $this->record->getUid() => [
                            $disabledFieldName => $value,
                        ],
                    ],
                ],
                'redirect' => $this->context->getCurrentRequest()->getAttribute('normalizedParams')->getRequestUri(),
            ]
        ) . '#element-' . $this->table . '-' . $this->record->getUid();
    }

    public function getVisibilityToggleTitle(): string
    {
        if ($this->getRow()[$this->getDisabledFieldName()] ?? false) {
            return $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:unHide');
        }
        return $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:hide');
    }

    public function getVisibilityToggleIconName(): string
    {
        return ($this->getRow()[$this->getDisabledFieldName()] ?? false) ? 'unhide' : 'hide';
    }

    public function isVisibilityToggling(): bool
    {
        $disabledFieldName = $this->getDisabledFieldName();
        return $disabledFieldName
            && $this->schema->hasField($disabledFieldName)
            && (
                !$this->schema->getField($disabledFieldName)->supportsAccessControl()
                || $this->getBackendUser()->check('non_exclude_fields', $this->table . ':' . $disabledFieldName)
            )
        ;
    }

    public function getEditUrl(): string
    {
        $urlParameters = [
            'edit' => [
                $this->table => [
                    $this->record->getUid() => 'edit',
                ],
            ],
            'module' => 'web_layout',
            'returnUrl' => $this->context->getCurrentRequest()->getAttribute('normalizedParams')->getRequestUri() . '#element-' . $this->table . '-' . $this->record->getUid(),
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return $uriBuilder->buildUriFromRoute('record_edit', $urlParameters) . '#element-' . $this->table . '-' . $this->record->getUid();
    }

    public function getTypeColumn(): string
    {
        // @todo This only supports local record types due to usages in this class
        return $this->schema->supportsSubSchema() && !$this->schema->getSubSchemaTypeInformation()->isPointerToForeignFieldInForeignSchema()
            ? $this->schema->getSubSchemaTypeInformation()->getFieldName()
            : '';
    }

    public function getRecordType(): string
    {
        return $this->record->getRecordType();
    }

    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Gets the number of records referencing the record with the UID $uid in
     * the current table.
     *
     * @return int The number of references to record $uid in table
     */
    protected function getReferenceCount(int $uid): int
    {
        return GeneralUtility::makeInstance(ReferenceIndex::class)->getNumberOfReferencedRecords($this->table, $uid);
    }

    protected function getLabelFromItemListMerged(): string
    {
        $table = $this->table;
        $typeColumn = $this->getTypeColumn();
        $recordType = $this->getRecordType();
        return BackendUtility::getLabelFromItemListMerged($this->record->getPid(), $table, $typeColumn, $recordType, $this->getRow());
    }

    protected function getDisabledFieldName(): ?string
    {
        return $this->schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField) ? (string)$this->schema->getCapability(TcaSchemaCapability::RestrictionDisabledField) : null;
    }
}
