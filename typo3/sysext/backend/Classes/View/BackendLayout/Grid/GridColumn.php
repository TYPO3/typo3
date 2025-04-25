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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\Event\AfterSectionMarkupGeneratedEvent;
use TYPO3\CMS\Backend\View\Event\BeforeSectionMarkupGeneratedEvent;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Grid Column
 *
 * Object representation (model/proxy) for a single column from a grid defined
 * in a BackendLayout. Stores GridColumnItem representations of content records
 * and provides getter methods which return various properties associated with
 * a single column, e.g. the "edit all elements in content" URL and the "add
 * new content element" URL of the button that is placed in the top of columns
 * in the page layout.
 *
 * Accessed from Fluid templates.
 *
 * @internal
 */
class GridColumn extends AbstractGridObject
{
    /**
     * @var GridColumnItem[]
     */
    protected array $items = [];

    protected readonly ?int $columnNumber;
    protected readonly string $columnName;
    protected readonly string $icon;
    protected readonly int $colSpan;
    protected readonly int $rowSpan;
    protected readonly ?string $identifier;
    private readonly EventDispatcherInterface $eventDispatcher;

    /**
     * @param array<string, mixed> $definition
     */
    public function __construct(
        protected PageLayoutContext $context,
        protected readonly array $definition,
        protected readonly string $table = 'tt_content'
    ) {
        parent::__construct($context);
        $this->columnNumber = isset($definition['colPos']) ? (int)$definition['colPos'] : null;
        $this->columnName = (string)($definition['name'] ?? 'default');
        $this->icon = (string)($definition['icon'] ?? '');
        $this->colSpan = (int)($definition['colspan'] ?? 1);
        $this->rowSpan = (int)($definition['rowspan'] ?? 1);
        $this->identifier = isset($definition['identifier']) ? (string)$definition['identifier'] : null;
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefinition(): array
    {
        return $this->definition;
    }

    public function isActive(): bool
    {
        return $this->columnNumber !== null && in_array($this->columnNumber, $this->context->getDrawingConfiguration()->getActiveColumns());
    }

    public function addItem(GridColumnItem $item): void
    {
        $this->items[] = $item;
    }

    /**
     * @return GridColumnItem[]
     */
    public function getItems(): iterable
    {
        return $this->items;
    }

    public function getColumnNumber(): ?int
    {
        return $this->columnNumber;
    }

    public function getColumnName(): string
    {
        return $this->columnName;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getColSpan(): int
    {
        if ($this->context->getDrawingConfiguration()->isLanguageComparisonMode()) {
            return 1;
        }
        return $this->colSpan;
    }

    public function getRowSpan(): int
    {
        if ($this->context->getDrawingConfiguration()->isLanguageComparisonMode()) {
            return 1;
        }
        return $this->rowSpan;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function getIdentifierCleaned(): string
    {
        return strtolower((string)preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$this->identifier));
    }

    /**
     * @return int[]
     */
    public function getAllContainedItemUids(): array
    {
        $uids = [];
        foreach ($this->items as $columnItem) {
            $uids[] = (int)$columnItem->getRecord()['uid'];
        }
        return $uids;
    }

    public function getEditUrl(): ?string
    {
        if (empty($this->items)) {
            return null;
        }
        $pageRecord = $this->context->getPageRecord();
        if (!$this->getBackendUser()->doesUserHaveAccess($pageRecord, Permission::CONTENT_EDIT)
            || !$this->getBackendUser()->checkLanguageAccess($this->context->getSiteLanguage())) {
            return null;
        }
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [
                $this->table => [
                    implode(',', $this->getAllContainedItemUids()) => 'edit',
                ],
            ],
            'module' => 'web_layout',
            'recTitle' => BackendUtility::getRecordTitle('pages', $pageRecord, true),
            'returnUrl' => $this->context->getCurrentRequest()->getAttribute('normalizedParams')->getRequestUri(),
        ]);
    }

    public function getNewContentUrl(): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $pageId = $this->context->getPageId();

        return (string)$uriBuilder->buildUriFromRoute('new_content_element_wizard', [
            'id' => $pageId,
            'sys_language_uid' => $this->context->getSiteLanguage()->getLanguageId(),
            'colPos' => $this->getColumnNumber(),
            'uid_pid' => $pageId,
            'returnUrl' => $this->context->getCurrentRequest()->getAttribute('normalizedParams')->getRequestUri(),
        ]);
    }

    public function getTitle(): string
    {
        $columnNumber = $this->getColumnNumber();
        $colTitle = '';
        foreach ($this->context->getBackendLayout()->getUsedColumns() as $colPos => $title) {
            if ($colPos === $columnNumber) {
                $colTitle = $this->getLanguageService()->sL($title);
            }
        }
        return $colTitle;
    }

    public function getTitleInaccessible(): string
    {
        return $this->getLanguageService()->sL($this->columnName) . ' (' . $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:noAccess') . ')';
    }

    public function getTitleUnassigned(): string
    {
        return $this->getLanguageService()->sL($this->columnName) . ' (' . $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:notAssigned') . ')';
    }

    public function getBeforeSectionMarkup(): string
    {
        $event = new BeforeSectionMarkupGeneratedEvent($this->definition, $this->context, $this->getRecords());
        $this->eventDispatcher->dispatch($event);
        return $event->getContent();
    }

    public function getAfterSectionMarkup(): string
    {
        $event = new AfterSectionMarkupGeneratedEvent($this->definition, $this->context, $this->getRecords());
        $this->eventDispatcher->dispatch($event);
        return $event->getContent();
    }

    public function isUnassigned(): bool
    {
        return $this->columnName !== 'unused' && $this->columnNumber === null;
    }

    public function isUnused(): bool
    {
        return $this->columnName === 'unused' && $this->columnNumber === null;
    }

    public function isContentEditable(): bool
    {
        if ($this->columnName === 'unused' || $this->columnNumber === null) {
            return false;
        }
        if ($this->getBackendUser()->isAdmin()) {
            return true;
        }
        $pageRecord = $this->context->getPageRecord();
        return $this->getBackendUser()->doesUserHaveAccess($pageRecord, Permission::CONTENT_EDIT)
            && $this->getBackendUser()->checkLanguageAccess($this->context->getSiteLanguage())
            && (
                !($pagesSchema = GeneralUtility::makeInstance(TcaSchemaFactory::class)->get('pages'))->hasCapability(TcaSchemaCapability::EditLock)
                || !($pageRecord[$pagesSchema->getCapability(TcaSchemaCapability::EditLock)->getFieldName()] ?? false)
            );
    }

    /**
     * Get the raw records for the current column
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getRecords(): array
    {
        if ($this->items === []) {
            return [];
        }

        $records = [];
        foreach ($this->items as $item) {
            $record = $item->getRecord();
            $records[(int)$record['uid']] = $record;
        }
        return $records;
    }
}
