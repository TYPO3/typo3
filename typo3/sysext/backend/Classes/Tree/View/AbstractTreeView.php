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

namespace TYPO3\CMS\Backend\Tree\View;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Domain\Repository\PageDescendantsRepository;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base class for creating a browsable array/page/folder tree in HTML
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
abstract class AbstractTreeView
{
    /**
     * Table of the tree records, used for their icons. The tree is always built from "pages".
     */
    protected string $table = 'pages';

    /**
     * WHERE clause used for selecting records for the tree. Is set by function init.
     * It is evaluated on the live page, columns may be qualified with "pages".
     *
     * @see init()
     */
    protected string $clause = '';

    /**
     * Field for ORDER BY. Is set by function init.
     * Pages are ordered in PHP, the fields must be part of $fieldArray.
     *
     * @see init()
     */
    public string $orderByFields = 'sorting';

    /**
     * Default set of fields selected from the tree table.
     * Make SURE that these fields names listed herein are actually possible to select from $this->table (if that variable is set to a TCA table name)
     *
     * @see addField()
     */
    protected array $fieldArray = [
        'uid',
        'pid',
        'title',
        'is_siteroot',
        'doktype',
        'nav_title',
        'mount_pid',
        'php_tree_stop',
        't3ver_state',
        'hidden',
        'starttime',
        'endtime',
        'fe_group',
        'module',
        'extendToSubpages',
        'nav_hide',
        't3ver_wsid',
        'crdate',
        'tstamp',
        'sorting',
        'deleted',
        'perms_userid',
        'perms_groupid',
        'perms_user',
        'perms_group',
        'perms_everybody',
        'editlock',
        'l18n_cfg',
    ];

    /**
     * If true, HTML code is also accumulated in ->tree array during rendering of the tree
     */
    public bool $makeHTML = true;

    // *********
    // Internal
    // *********
    // For record trees:
    // one-dim array of the uid's selected.
    protected array $ids = [];

    // The hierarchy of element uids
    protected array $ids_hierarchy = [];

    // The hierarchy of versioned element uids
    public array $orig_ids_hierarchy = [];

    // Temporary, internal array
    public array $buffer_idH = [];

    // For both types
    // Tree is accumulated in this variable
    public array $tree = [];

    /**
     * @var array<int, list<array<string, mixed>>>|null Pages by parent page id while the tree is built
     */
    private ?array $subpages = null;

    /**
     * @param string $clause Record WHERE clause
     * @param string $orderByFields Record ORDER BY field
     */
    public function init($clause = '', $orderByFields = '')
    {
        if ($clause) {
            $this->clause = $clause;
        }
        if ($orderByFields) {
            $this->orderByFields = $orderByFields;
        }
    }

    public function addField(string $field): void
    {
        $this->fieldArray[] = $field;
    }

    /**
     * Resets the tree, recs, ids, ids_hierarchy and orig_ids_hierarchy internal variables. Use it if you need it.
     */
    protected function reset(): void
    {
        $this->tree = [];
        $this->ids = [];
        $this->ids_hierarchy = [];
        $this->orig_ids_hierarchy = [];
    }

    /*******************************************
     *
     * rendering parts
     *
     *******************************************/
    /**
     * Generate the plus/minus icon for the browsable tree.
     *
     * @param array $row Record for the entry
     * @param int $a The current entry number
     * @param int $c The total number of entries. If equal to $a, a "bottom" element is returned.
     * @param int $nextCount The number of sub-elements to the current element.
     * @param bool $isOpen The element was expanded to render subelements if this flag is set.
     * @return string Image tag with the plus/minus icon.
     * @see \TYPO3\CMS\Backend\Tree\View\PageTreeView::PMicon()
     */
    protected function PMicon($row, $a, $c, $nextCount, $isOpen)
    {
        if ($nextCount) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

            // Wrap the plus/minus icon in a link
            $anchor = $row['uid'] ? '#' . $row['uid'] : '';
            $name = $row['uid'] ? ' name="' . $row['uid'] . '"' : '';
            $aUrl = $anchor;
            if ($isOpen) {
                $class = 'treelist-control-open';
                $icon = $iconFactory->getIcon('actions-chevron-down', IconSize::SMALL);
            } else {
                $class = 'treelist-control-collapsed';
                $icon = $iconFactory->getIcon('actions-chevron-end', IconSize::SMALL);
            }
            return '<a class="treelist-control ' . $class . '" href="' . htmlspecialchars($aUrl) . '"' . $name . '>' . $icon->render(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE) . '</a>';
        }
        return '';
    }

    /*******************************************
     *
     * tree handling
     *
     *******************************************/
    /**
     * Returns TRUE/FALSE if the next level for $id should be expanded - based on
     * data in $this->stored[][] and ->expandAll flag.
     * Used in subclasses
     *
     * @param int $id Record id/key
     * @return bool
     * @internal
     * @see \TYPO3\CMS\Backend\Tree\View\PageTreeView::expandNext()
     */
    public function expandNext($id)
    {
        return false;
    }

    /******************************
     *
     * Functions that might be overwritten by extended classes
     *
     ********************************/

    /**
     * Get the icon markup for the row
     *
     * @param array $row The row to get the icon for
     * @return string The icon markup, wrapped into a span tag, with the records title as title attribute
     */
    protected function getIcon(array $row): string
    {
        $title = $this->getTitleAttrib($row);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $icon = $row['is_siteroot'] ? $iconFactory->getIcon('apps-pagetree-folder-root', IconSize::SMALL) : $iconFactory->getIconForRecord($this->table, $row, IconSize::SMALL);
        return $icon->setTitle($title)->render();
    }

    /**
     * Returns the value for the image "title" attribute
     *
     * @param array $row The input row array (where the key "title" is used for the title)
     * @return string The attribute value (is htmlspecialchared() already)
     */
    protected function getTitleAttrib($row)
    {
        return htmlspecialchars($row['title']);
    }

    /********************************
     *
     * tree data building
     *
     ********************************/
    /**
     * Fetches the data for the tree
     *
     * The pages of all levels are fetched with one query on the first call, the recursive
     * calls for the levels below work on these pages.
     *
     * @param int $uid item id for which to select subitems (parent id)
     * @param int $depth Max depth (recursivity limit)
     * @param string $depthData HTML-code prefix for recursive calls.
     * @return int<0, max> The count of items on the level
     */
    public function getTree(int $uid, int $depth = 999, string $depthData = ''): int
    {
        $isFirstCall = $this->subpages === null;
        if ($isFirstCall) {
            $this->subpages = $this->fetchSubpages($uid, $depth);
        }
        try {
            return $this->renderSubtree($uid, $depth, $depthData, $isFirstCall);
        } finally {
            if ($isFirstCall) {
                $this->subpages = null;
            }
        }
    }

    /**
     * @return int<0, max>
     */
    private function renderSubtree(int $uid, int $depth, string $depthData, bool $checkWebMount): int
    {
        // Buffer for id hierarchy is reset:
        $this->buffer_idH = [];
        // Init vars
        $HTML = '';
        $a = 0;
        $rows = $this->subpages[$uid] ?? [];
        if ($checkWebMount) {
            // Subpages of a page within a web mount are within the web mount as well
            $rows = array_values(array_filter($rows, fn(array $row): bool => $this->getBackendUser()->isInWebMount($row) !== null));
        }
        $c = count($rows);
        $idH = [];
        // Traverse the records:
        foreach ($rows as $row) {
            $a++;
            $newID = $row['uid'];
            // Reserve space.
            $this->tree[] = [];
            end($this->tree);
            // Get the key for this space
            $treeKey = key($this->tree);
            // Accumulate the id of the element in the internal arrays
            $this->ids[] = ($idH[$row['uid']]['uid'] = $row['uid']);
            $this->ids_hierarchy[$depth][] = $row['uid'];
            $this->orig_ids_hierarchy[$depth][] = (!empty($row['_ORIG_uid'])) ? $row['_ORIG_uid'] : $row['uid'];

            // Make a recursive call to the next level
            $nextLevelDepthData = $depthData . '<span class="treeline-icon treeline-icon-' . ($a === $c ? 'clear' : 'line') . '"></span>';
            $hasSub = $this->expandNext($newID) && !($row['php_tree_stop'] ?? false);
            if ($depth > 1 && $hasSub) {
                $nextCount = $this->renderSubtree($newID, $depth - 1, $nextLevelDepthData, false);
                if (!empty($this->buffer_idH)) {
                    $idH[$row['uid']]['subrow'] = $this->buffer_idH;
                }
                // Set "did expand" flag
                $isOpen = true;
            } else {
                $nextCount = count($this->subpages[$newID] ?? []);
                // Clear "did expand" flag
                $isOpen = false;
            }
            // Set HTML-icons, if any:
            if ($this->makeHTML) {
                $HTML = $this->PMicon($row, $a, $c, $nextCount, $isOpen);
            }
            // Finally, add the row/HTML content to the ->tree array in the reserved key.
            $this->tree[$treeKey] = [
                'row' => $row,
                'HTML' => $HTML,
                'icon' => $this->getIcon($row),
                'invertedDepth' => $depth,
                'depthData' => $depthData,
                'hasSub' => $nextCount && $hasSub,
                'isFirst' => $a === 1,
                'isLast' => $a === $c,
            ];
        }

        $this->buffer_idH = $idH;
        return $c;
    }

    /********************************
     *
     * Data handling
     * Works with records and arrays
     *
     ********************************/
    /**
     * Fetches the workspace-resolved subpages of a page down to the given depth, and one
     * level more to know whether the pages on the last level have subpages.
     *
     * @return array<int, list<array<string, mixed>>> Pages by parent page id, in the order of $this->orderByFields
     */
    protected function fetchSubpages(int $uid, int $depth): array
    {
        $clause = QueryHelper::stripLogicalOperatorPrefix($this->clause);
        $pages = GeneralUtility::makeInstance(PageDescendantsRepository::class)->findDescendants(
            [$uid],
            min($depth, PageDescendantsRepository::MAX_TRAVERSAL_LEVELS - 1) + 1,
            (int)$this->getBackendUser()->workspace,
            $clause !== '' ? static fn(): string => $clause : null,
            [],
            $this->fieldArray,
            false,
            true,
        );
        $subpages = [];
        foreach ($pages as $page) {
            $subpages[$page['pid']][] = $page;
        }
        $orderBy = array_map(
            static fn(array $orderByField): array => [preg_replace('/^pages\./', '', $orderByField[0]), $orderByField[1]],
            QueryHelper::parseOrderBy($this->orderByFields)
        );
        if ($orderBy !== [['sorting', null]]) {
            foreach ($subpages as &$pagesOfParent) {
                usort($pagesOfParent, static function (array $a, array $b) use ($orderBy): int {
                    foreach ($orderBy as [$fieldName, $order]) {
                        $valueA = $a[$fieldName] ?? null;
                        $valueB = $b[$fieldName] ?? null;
                        $result = is_string($valueA) && is_string($valueB) && !(is_numeric($valueA) && is_numeric($valueB))
                            ? strnatcasecmp($valueA, $valueB)
                            : $valueA <=> $valueB;
                        if ($result !== 0) {
                            return strtoupper((string)$order) === 'DESC' ? -$result : $result;
                        }
                    }
                    return 0;
                });
            }
            unset($pagesOfParent);
        }
        return $subpages;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
