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

namespace TYPO3\CMS\Impexp\View;

use TYPO3\CMS\Backend\Configuration\BackendUserConfiguration;
use TYPO3\CMS\Backend\Tree\View\AbstractTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Export;

/**
 * Extension of the page tree class. Used to get the tree of pages to export.
 *
 * @internal This class is not considered part of the public TYPO3 API.
 */
class ExportPageTreeView extends AbstractTreeView
{
    /**
     * If set, then ALL items will be expanded, regardless of stored settings.
     * @var bool
     */
    protected $expandAll = false;

    /**
     * Points to the current mountpoint key
     * @var int
     */
    public $bank = 0;

    /**
     * Holds (session stored) information about which items in the tree are unfolded and which are not.
     * @var array
     */
    public $stored = [];

    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    /**
     * Init function
     * REMEMBER to feed a $clause which will filter out non-readable pages!
     *
     * @param string $clause Part of where query which will filter out non-readable pages.
     * @param string $orderByFields Record ORDER BY field
     */
    public function init($clause = '', $orderByFields = '')
    {
        parent::init(' AND deleted=0 AND sys_language_uid=0 ' . $clause, $orderByFields ?: 'sorting');
    }

    /**
     * Creates title attribute content for pages.
     * Uses API function in \TYPO3\CMS\Backend\Utility\BackendUtility which will retrieve lots of useful information for pages.
     *
     * @param array $row The table row.
     * @return string
     */
    public function getTitleAttrib($row)
    {
        return BackendUtility::titleAttribForPages($row, '1=1 ' . $this->clause, false);
    }

    /**
     * Wrapping Plus/Minus icon, unused in Export Page Tree
     */
    public function PMicon($row, $a, $c, $nextCount, $isOpen)
    {
        return '';
    }

    /**
     * Construction of the tree structure with predefined depth.
     *
     * @param int $pid Page ID
     * @param int $levels Page tree levels
     */
    public function buildTreeByLevels(int $pid, int $levels): void
    {
        $this->expandAll = true;
        $checkSub = $levels > 0;

        $this->buildTree($pid, $levels, $checkSub);
    }

    /**
     * Construction of the tree structure according to the state of folding of the page tree module.
     *
     * @param int $pid Page ID
     */
    public function buildTreeByExpandedState(int $pid): void
    {
        $this->syncPageTreeState();

        $this->expandAll = false;
        if ($pid > 0) {
            $checkSub = (bool)($this->stored[$this->bank][$pid] ?? false);
        } else {
            $checkSub = true;
        }

        $this->buildTree($pid, Export::LEVELS_INFINITE, $checkSub);
    }

    /**
     * Creation of a tree structure with predefined depth to prepare the export.
     *
     * @param int $pid Page ID
     * @param int $levels Page tree levels
     * @param bool $checkSub Should root page be checked for sub pages?
     */
    protected function buildTree(int $pid, int $levels, bool $checkSub): void
    {
        $this->reset();

        // Root page
        if ($pid > 0) {
            $rootRecord = BackendUtility::getRecordWSOL('pages', $pid);
            $rootHtml = $this->getPageIcon($rootRecord);
        } else {
            $rootRecord = [
                'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
                'uid' => 0,
            ];
            $rootHtml = $this->getRootIcon($rootRecord);
        }

        $this->tree[] = [
            'HTML' => $rootHtml,
            'row' => $rootRecord,
            'hasSub' => $checkSub,
            'bank' => $this->bank,
        ];

        // Subtree
        if ($checkSub) {
            $this->getTree($pid, $levels);
        }

        $idH = [];
        $idH[$pid]['uid'] = $pid;
        if (!empty($this->buffer_idH)) {
            $idH[$pid]['subrow'] = $this->buffer_idH;
        }
        $this->buffer_idH = $idH;

        // Check if root page has subtree
        if (empty($this->buffer_idH)) {
            $this->tree[0]['hasSub'] = false;
        }
    }

    /**
     * Sync folding state of EXT:impexp page tree with the official page tree module
     */
    protected function syncPageTreeState(): void
    {
        $backendUserConfiguration = GeneralUtility::makeInstance(BackendUserConfiguration::class);
        $pageTreeState = $backendUserConfiguration->get('BackendComponents.States.Pagetree');
        if (is_object($pageTreeState) && is_object($pageTreeState->stateHash)) {
            $pageTreeState = (array)$pageTreeState->stateHash;
        } else {
            $stateHash = $pageTreeState['stateHash'] ?? [];
            $pageTreeState = is_array($stateHash) ? $stateHash : [];
        }

        $this->stored = [];
        foreach ($pageTreeState as $identifier => $isExpanded) {
            list($bank, $pageId) = explode('_', $identifier);
            $this->stored[$bank][$pageId] = $isExpanded;
        }
    }

    /**
     * Compiles the HTML code for displaying the structure found inside the ->tree array
     *
     * @param array|string $treeArr "tree-array" - if blank string, the internal ->tree array is used.
     * @return string The HTML code for the tree
     */
    public function printTree($treeArr = '')
    {
        $titleLen = (int)$this->getBackendUser()->uc['titleLen'];
        if (!is_array($treeArr)) {
            $treeArr = $this->tree;
        }
        $out = '';
        $closeDepth = [];
        foreach ($treeArr as $treeItem) {
            $classAttr = '';
            if ($treeItem['isFirst'] ?? false) {
                $out .= '<ul class="list-tree">';
            }

            // Add CSS classes to the list item
            if ($treeItem['hasSub'] ?? false) {
                $classAttr .= ' list-tree-control-open';
            }

            $idAttr = htmlspecialchars('pages' . $treeItem['row']['uid']);
            $out .= '
				<li id="' . $idAttr . '"' . ($classAttr ? ' class="' . trim($classAttr) . '"' : '') . '>
					<span class="list-tree-group">
						<span class="list-tree-icon">' . $treeItem['HTML'] . '</span>
						<span class="list-tree-title">' . $this->getTitleStr($treeItem['row'], $titleLen) . '</span>
					</span>';

            if (!($treeItem['hasSub'] ?? false)) {
                $out .= '</li>';
            }

            // We have to remember if this is the last one
            // on level X so the last child on level X+1 closes the <ul>-tag
            if ($treeItem['isLast'] ?? false) {
                $closeDepth[$treeItem['invertedDepth']] = 1;
            }
            // If this is the last one and does not have subitems, we need to close
            // the tree as long as the upper levels have last items too
            if (($treeItem['isLast'] ?? false) && !($treeItem['hasSub'] ?? false)) {
                for ($i = $treeItem['invertedDepth']; ($closeDepth[$i] ?? 0) == 1; $i++) {
                    $closeDepth[$i] = 0;
                    $out .= '</ul></li>';
                }
            }
        }
        return '<ul class="list-tree list-tree-root list-tree-root-clean">' . $out . '</ul>';
    }

    /**
     * Returns TRUE/FALSE if the next level for $id should be expanded - based on
     * data in $this->stored[][] and ->expandAll flag.
     * Extending parent function
     *
     * @param int $id Record id/key
     * @return bool
     * @internal
     * @see \TYPO3\CMS\Backend\Tree\View\PageTreeView::expandNext()
     */
    public function expandNext($id)
    {
        return $this->expandAll || !empty($this->stored[$this->bank][$id]);
    }

    /**
     * Get page icon for the row.
     *
     * @param array $row
     * @return string Icon image tag.
     */
    protected function getPageIcon(array $row): string
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        return $iconFactory->getIconForRecord($this->table, $row, Icon::SIZE_SMALL)->render();
    }
}
