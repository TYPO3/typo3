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
 */
class GridColumn extends AbstractGridObject
{
    /**
     * @var GridColumnItem[]
     */
    protected $items = [];

    /**
     * @var int|null
     */
    protected $columnNumber;

    /**
     * @var string
     */
    protected $columnName = 'default';

    /**
     * @var string|null
     */
    protected $icon;

    /**
     * @var int
     */
    protected $colSpan = 1;

    /**
     * @var int
     */
    protected $rowSpan = 1;

    /**
     * @var array
     */
    protected $records;

    public function __construct(BackendLayout $backendLayout, array $columnDefinition, ?array $records = null)
    {
        parent::__construct($backendLayout);
        $this->columnNumber = isset($columnDefinition['colPos']) ? (int)$columnDefinition['colPos'] : $this->columnNumber;
        $this->columnName = $columnDefinition['name'] ?? $this->columnName;
        $this->icon = $columnDefinition['icon'] ?? $this->icon;
        $this->colSpan = (int)($columnDefinition['colspan'] ?? $this->colSpan);
        $this->rowSpan = (int)($columnDefinition['rowspan'] ?? $this->rowSpan);
        if ($this->columnNumber !== null) {
            $this->records = $records ?? $backendLayout->getContentFetcher()->getContentRecordsPerColumn($this->columnNumber, $backendLayout->getDrawingConfiguration()->getLanguageColumnsPointer());
            foreach ($this->records as $contentRecord) {
                $columnItem = GeneralUtility::makeInstance(GridColumnItem::class, $backendLayout, $this, $contentRecord);
                $this->addItem($columnItem);
            }
        }
    }

    public function isActive(): bool
    {
        return $this->columnNumber !== null && in_array($this->columnNumber, $this->backendLayout->getDrawingConfiguration()->getActiveColumns());
    }

    public function addItem(GridColumnItem $item): void
    {
        $this->items[] = $item;
    }

    public function getRecords(): iterable
    {
        return $this->records;
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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getColSpan(): int
    {
        if ($this->backendLayout->getDrawingConfiguration()->getLanguageMode()) {
            return 1;
        }
        return $this->colSpan;
    }

    public function getRowSpan(): int
    {
        if ($this->backendLayout->getDrawingConfiguration()->getLanguageMode()) {
            return 1;
        }
        return $this->rowSpan;
    }

    public function getAllContainedItemUids(): iterable
    {
        $uids = [];
        foreach ($this->items as $columnItem) {
            $uids[] = $columnItem->getRecord()['uid'];
        }
        return $uids;
    }

    public function getEditUrl(): ?string
    {
        if (empty($this->items)) {
            return null;
        }
        $pageRecord = $this->backendLayout->getDrawingConfiguration()->getPageRecord();
        if (!$this->getBackendUser()->doesUserHaveAccess($pageRecord, Permission::CONTENT_EDIT)
            && !$this->getBackendUser()->checkLanguageAccess(0)) {
            return null;
        }
        $pageTitleParamForAltDoc = '&recTitle=' . rawurlencode(
            BackendUtility::getRecordTitle('pages', $pageRecord, true)
        );
        $editParam = '&edit[tt_content][' . implode(',', $this->getAllContainedItemUids()) . ']=edit' . $pageTitleParamForAltDoc;
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return $uriBuilder->buildUriFromRoute('record_edit') . $editParam . '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));
    }

    public function getNewContentUrl(): string
    {
        $pageId = $this->backendLayout->getDrawingConfiguration()->getPageId();
        $urlParameters = [
            'id' => $pageId,
            'sys_language_uid' => $this->backendLayout->getDrawingConfiguration()->getLanguageColumnsPointer(),
            'colPos' => $this->getColumnNumber(),
            'uid_pid' => $pageId,
            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
        ];
        $routeName = BackendUtility::getPagesTSconfig($pageId)['mod.']['newContentElementWizard.']['override']
            ?? 'new_content_element_wizard';
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute($routeName, $urlParameters);
    }

    public function getTitle(): string
    {
        $columnNumber = $this->getColumnNumber();
        $colTitle = (string)BackendUtility::getProcessedValue('tt_content', 'colPos', $columnNumber);
        $tcaItems = $this->backendLayout->getConfigurationArray()['__items'];
        foreach ($tcaItems as $item) {
            if ($item[1] === $columnNumber) {
                $colTitle = (string)$this->getLanguageService()->sL($item[0]);
            }
        }
        return $colTitle;
    }

    public function getTitleInaccessible(): string
    {
        return $this->getLanguageService()->sL($this->columnName) . ' (' . $this->getLanguageService()->getLL('noAccess') . ')';
    }

    public function getTitleUnassigned(): string
    {
        return $this->getLanguageService()->getLL('notAssigned');
    }

    public function isUnassigned(): bool
    {
        return $this->columnNumber === null;
    }

    public function isContentEditable(): bool
    {
        if ($this->columnName === 'unused' || $this->columnNumber === null) {
            return false;
        }
        if ($this->getBackendUser()->isAdmin()) {
            return true;
        }
        $pageRecord = $this->backendLayout->getDrawingConfiguration()->getPageRecord();
        return !$pageRecord['editlock']
            && $this->getBackendUser()->doesUserHaveAccess($pageRecord, Permission::CONTENT_EDIT)
            && $this->getBackendUser()->checkLanguageAccess($this->backendLayout->getDrawingConfiguration()->getLanguageColumnsPointer());
    }
}
