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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageLayoutContext;
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
 * @internal this is experimental and subject to change in TYPO3 v10 / v11
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

    public function __construct(PageLayoutContext $context, array $columnDefinition)
    {
        parent::__construct($context);
        $this->columnNumber = isset($columnDefinition['colPos']) ? (int)$columnDefinition['colPos'] : $this->columnNumber;
        $this->columnName = $columnDefinition['name'] ?? $this->columnName;
        $this->icon = $columnDefinition['icon'] ?? $this->icon;
        $this->colSpan = (int)($columnDefinition['colspan'] ?? $this->colSpan);
        $this->rowSpan = (int)($columnDefinition['rowspan'] ?? $this->rowSpan);
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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getColSpan(): int
    {
        if ($this->context->getDrawingConfiguration()->getLanguageMode()) {
            return 1;
        }
        return $this->colSpan;
    }

    public function getRowSpan(): int
    {
        if ($this->context->getDrawingConfiguration()->getLanguageMode()) {
            return 1;
        }
        return $this->rowSpan;
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
            || !$this->getBackendUser()->checkLanguageAccess($this->context->getSiteLanguage()->getLanguageId())) {
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
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $pageId = $this->context->getPageId();

        if ($this->context->getDrawingConfiguration()->getShowNewContentWizard()) {
            $urlParameters = [
                'id' => $pageId,
                'sys_language_uid' => $this->context->getSiteLanguage()->getLanguageId(),
                'colPos' => $this->getColumnNumber(),
                'uid_pid' => $pageId,
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ];
            $routeName = BackendUtility::getPagesTSconfig($pageId)['mod.']['newContentElementWizard.']['override']
                ?? 'new_content_element_wizard';
        } else {
            $urlParameters = [
                'edit' => [
                    'tt_content' => [
                        $pageId => 'new'
                    ]
                ],
                'defVals' => [
                    'tt_content' => [
                        'colPos' => $this->getColumnNumber(),
                        'sys_language_uid' => $this->context->getSiteLanguage()->getLanguageId()
                    ]
                ],
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ];
            $routeName = 'record_edit';
        }

        return (string)$uriBuilder->buildUriFromRoute($routeName, $urlParameters);
    }

    public function getTitle(): string
    {
        $columnNumber = $this->getColumnNumber();
        $colTitle = BackendUtility::getProcessedValue('tt_content', 'colPos', (string)$columnNumber) ?? '';
        foreach ($this->context->getBackendLayout()->getUsedColumns() as $colPos => $title) {
            if ($colPos === $columnNumber) {
                $colTitle = (string)$this->getLanguageService()->sL($title);
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
        return $this->getTitle() . ' (' . $this->getLanguageService()->getLL('notAssigned') . ')';
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
        $pageRecord = $this->context->getPageRecord();
        return !$pageRecord['editlock']
            && $this->getBackendUser()->doesUserHaveAccess($pageRecord, Permission::CONTENT_EDIT)
            && $this->getBackendUser()->checkLanguageAccess($this->context->getSiteLanguage()->getLanguageId());
    }
}
