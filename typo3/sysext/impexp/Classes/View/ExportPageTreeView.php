<?php

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

use TYPO3\CMS\Backend\Tree\View\AbstractTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extension of the page tree class. Used to get the tree of pages to export.
 * @internal
 */
class ExportPageTreeView extends AbstractTreeView
{
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
     * Tree rendering
     *
     * @param int $pid PID value
     * @param string $clause Additional where clause
     * @return array Array of tree elements
     */
    public function ext_tree($pid, $clause = '')
    {
        // Initialize:
        $this->init(' AND ' . $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW) . $clause);
        // Get stored tree structure:
        $this->stored = json_decode($this->getBackendUser()->uc['browseTrees']['browsePages'], true);
        $treeArr = [];
        $idx = 0;
        // Set first:
        $this->bank = $idx;
        $isOpen = $this->stored[$idx][$pid];
        // save ids
        $curIds = $this->ids;
        $this->reset();
        $this->ids = $curIds;
        if ($pid > 0) {
            $rootRec = BackendUtility::getRecordWSOL('pages', $pid);
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $firstHtml = $iconFactory->getIconForRecord('pages', $rootRec, Icon::SIZE_SMALL)->render();
        } else {
            $rootRec = [
                'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
                'uid' => 0
            ];
            $firstHtml = $this->getRootIcon($rootRec);
        }
        $this->tree[] = ['HTML' => $firstHtml, 'row' => $rootRec, 'hasSub' => $isOpen];
        if ($isOpen) {
            $this->getTree($pid);
            $idH = [];
            $idH[$pid]['uid'] = $pid;
            if (!empty($this->buffer_idH)) {
                $idH[$pid]['subrow'] = $this->buffer_idH;
            }
            $this->buffer_idH = $idH;
        }
        // Add tree:
        return array_merge($treeArr, $this->tree);
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
            if ($treeItem['isFirst']) {
                $out .= '<ul class="list-tree">';
            }

            // Add CSS classes to the list item
            if ($treeItem['hasSub']) {
                $classAttr .= ' list-tree-control-open';
            }

            $idAttr = htmlspecialchars('pages' . $treeItem['row']['uid']);
            $out .= '
				<li id="' . $idAttr . '"' . ($classAttr ? ' class="' . trim($classAttr) . '"' : '') . '>
					<span class="list-tree-group">
						<span class="list-tree-icon">' . $treeItem['HTML'] . '</span>
						<span class="list-tree-title">' . $this->getTitleStr($treeItem['row'], $titleLen) . '</span>
					</span>';

            if (!$treeItem['hasSub']) {
                $out .= '</li>';
            }

            // We have to remember if this is the last one
            // on level X so the last child on level X+1 closes the <ul>-tag
            if ($treeItem['isLast']) {
                $closeDepth[$treeItem['invertedDepth']] = 1;
            }
            // If this is the last one and does not have subitems, we need to close
            // the tree as long as the upper levels have last items too
            if ($treeItem['isLast'] && !$treeItem['hasSub']) {
                for ($i = $treeItem['invertedDepth']; $closeDepth[$i] == 1; $i++) {
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
        return !empty($this->stored[$this->bank][$id]);
    }
}
