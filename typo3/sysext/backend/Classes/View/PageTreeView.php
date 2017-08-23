<?php
namespace TYPO3\CMS\Backend\View;

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

use TYPO3\CMS\Backend\Tree\View\BrowseTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Browse pages in Web module
 */
class PageTreeView extends BrowseTreeView
{
    /**
     * @var bool
     */
    public $ext_showPageId = false;

    /**
     * Indicates, whether the ajax call was successful, i.e. the requested page has been found
     *
     * @var bool
     */
    public $ajaxStatus = false;

    /**
     * Calls init functions
     */
    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    /**
     * Wrapping icon in browse tree
     *
     * @param string $thePageIcon Icon IMG code
     * @param array $row Data row for element.
     * @return string Page icon
     */
    public function wrapIcon($thePageIcon, $row)
    {
        /** @var $iconFactory IconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        // If the record is locked, present a warning sign.
        if ($lockInfo = BackendUtility::isRecordLocked('pages', $row['uid'])) {
            $aOnClick = 'alert(' . GeneralUtility::quoteJSvalue($lockInfo['msg']) . ');return false;';
            $lockIcon = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">'
                . '<span title="' . htmlspecialchars($lockInfo['msg']) . '">' . $iconFactory->getIcon('status-warning-in-use', Icon::SIZE_SMALL)->render() . '</span></a>';
        } else {
            $lockIcon = '';
        }
        // Wrap icon in click-menu link.
        if (!$this->ext_IconMode) {
            $thePageIcon = BackendUtility::wrapClickMenuOnIcon($thePageIcon, 'pages', $row['uid'], 'tree');
        } elseif ($this->ext_IconMode === 'titlelink') {
            $aOnClick = 'return jumpTo(' . GeneralUtility::quoteJSvalue($this->getJumpToParam($row)) . ',this,' . GeneralUtility::quoteJSvalue($this->treeName) . ');';
            $thePageIcon = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $thePageIcon . '</a>';
        }
        // Wrap icon in a drag/drop span.
        $dragDropIcon = '<span class="list-tree-icon dragIcon" id="dragIconID_' . $row['uid'] . '">' . $thePageIcon . '</span> ';
        // Add Page ID:
        $pageIdStr = '';
        if ($this->ext_showPageId) {
            $pageIdStr = '<span class="dragId">[' . $row['uid'] . ']</span> ';
        }
        // Call stats information hook
        $stat = '';
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
            $_params = ['pages', $row['uid']];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef) {
                $stat .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        return $dragDropIcon . $lockIcon . $pageIdStr . $stat;
    }

    /**
     * Wrapping $title in a-tags.
     *
     * @param string $title Title string
     * @param string $row Item record
     * @param int $bank Bank pointer (which mount point number)
     * @return string
     * @access private
     */
    public function wrapTitle($title, $row, $bank = 0)
    {
        // Hook for overriding the page title
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.webpagetree.php']['pageTitleOverlay'])) {
            $_params = ['title' => &$title, 'row' => &$row];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.webpagetree.php']['pageTitleOverlay'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
            unset($_params);
        }
        $aOnClick = 'return jumpTo(' . GeneralUtility::quoteJSvalue($this->getJumpToParam($row)) . ',this,' . GeneralUtility::quoteJSvalue($this->domIdPrefix . $this->getId($row)) . ',' . $bank . ');';
        $clickMenuParts = BackendUtility::wrapClickMenuOnIcon('', 'pages', $row['uid'], 'tree', '', '', true);

        $thePageTitle = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '"' . GeneralUtility::implodeAttributes($clickMenuParts) . '>' . $title . '</a>';
        // Wrap title in a drag/drop span.
        return '<span class="list-tree-title dragTitle" id="dragTitleID_' . $row['uid'] . '">' . $thePageTitle . '</span>';
    }

    /**
     * Compiles the HTML code for displaying the structure found inside the ->tree array
     *
     * @param array|string $treeArr "tree-array" - if blank string, the internal ->tree array is used.
     * @return string The HTML code for the tree
     */
    public function printTree($treeArr = '')
    {
        $titleLen = (int)$this->BE_USER->uc['titleLen'];
        if (!is_array($treeArr)) {
            $treeArr = $this->tree;
        }
        $out = '<ul class="list-tree list-tree-root">';
        // -- evaluate AJAX request
        // IE takes anchor as parameter
        $PM = GeneralUtility::_GP('PM');
        if (($PMpos = strpos($PM, '#')) !== false) {
            $PM = substr($PM, 0, $PMpos);
        }
        $PM = explode('_', $PM);

        $doCollapse = false;
        $doExpand = false;
        $expandedPageUid = null;
        $collapsedPageUid = null;
        if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX && is_array($PM) && count($PM) === 4 && $PM[2] != 0) {
            if ($PM[1]) {
                $expandedPageUid = $PM[2];
                $doExpand = true;
            } else {
                $collapsedPageUid = $PM[2];
                $doCollapse = true;
            }
        }
        // We need to count the opened <ul>'s every time we dig into another level,
        // so we know how many we have to close when all children are done rendering
        $closeDepth = [];
        $ajaxOutput = '';
        $invertedDepthOfAjaxRequestedItem = 0;
        foreach ($treeArr as $k => $treeItem) {
            $classAttr = $treeItem['row']['_CSSCLASS'];
            $uid = $treeItem['row']['uid'];
            $idAttr = htmlspecialchars($this->domIdPrefix . $this->getId($treeItem['row']) . '_' . $treeItem['bank']);
            $itemHTML = '';
            // If this item is the start of a new level,
            // then a new level <ul> is needed, but not in ajax mode
            if ($treeItem['isFirst'] && !$doCollapse && (!$doExpand || (int)$expandedPageUid !== (int)$uid)) {
                $itemHTML = '<ul class="list-tree">';
            }

            // Add CSS classes to the list item
            if ($treeItem['hasSub']) {
                $classAttr .= ' list-tree-control-open';
            }
            $itemHTML .= '<li id="' . $idAttr . '" ' . ($classAttr ? ' class="' . trim($classAttr) . '"' : '')
                . '><span class="list-tree-group">' . $treeItem['HTML']
                . $this->wrapTitle($this->getTitleStr($treeItem['row'], $titleLen), $treeItem['row'], $treeItem['bank']) . '</span>';
            if (!$treeItem['hasSub']) {
                $itemHTML .= '</li>';
            }

            // We have to remember if this is the last one
            // on level X so the last child on level X+1 closes the <ul>-tag
            if ($treeItem['isLast'] && !($doExpand && $expandedPageUid == $uid)) {
                $closeDepth[$treeItem['invertedDepth']] = 1;
            }
            // If this is the last one and does not have subitems, we need to close
            // the tree as long as the upper levels have last items too
            if ($treeItem['isLast'] && !$treeItem['hasSub'] && !$doCollapse && !($doExpand && $expandedPageUid == $uid)) {
                for ($i = $treeItem['invertedDepth']; $closeDepth[$i] == 1; $i++) {
                    $closeDepth[$i] = 0;
                    $itemHTML .= '</ul></li>';
                }
            }
            // Ajax request: collapse
            if ($doCollapse && (int)$collapsedPageUid === (int)$uid) {
                $this->ajaxStatus = true;
                return $itemHTML;
            }
            // ajax request: expand
            if ($doExpand && (int)$expandedPageUid === (int)$uid) {
                $ajaxOutput .= $itemHTML;
                $invertedDepthOfAjaxRequestedItem = $treeItem['invertedDepth'];
            } elseif ($invertedDepthOfAjaxRequestedItem) {
                if ($treeItem['invertedDepth'] < $invertedDepthOfAjaxRequestedItem) {
                    $ajaxOutput .= $itemHTML;
                } else {
                    $this->ajaxStatus = true;
                    return $ajaxOutput;
                }
            }
            $out .= $itemHTML;
        }
        if ($ajaxOutput) {
            $this->ajaxStatus = true;
            return $ajaxOutput;
        }
        // Finally close the first ul
        $out .= '</ul>';
        return $out;
    }

    /**
     * Generate the plus/minus icon for the browsable tree.
     *
     * @param array $row Record for the entry
     * @param int $a The current entry number
     * @param int $c The total number of entries. If equal to $a, a "bottom" element is returned.
     * @param int $nextCount The number of sub-elements to the current element.
     * @param bool $exp The element was expanded to render subelements if this flag is set.
     * @return string Image tag with the plus/minus icon.
     * @access private
     * @see \TYPO3\CMS\Backend\Tree\View\PageTreeView::PMicon()
     */
    public function PMicon($row, $a, $c, $nextCount, $exp)
    {
        $icon = '';
        if ($nextCount) {
            $cmd = $this->bank . '_' . ($exp ? '0_' : '1_') . $row['uid'] . '_' . $this->treeName;
            $icon = $this->PMiconATagWrap($icon, $cmd, !$exp);
        }
        return $icon;
    }

    /**
     * Wrap the plus/minus icon in a link
     *
     * @param string $icon HTML string to wrap, probably an image tag.
     * @param string $cmd Command for 'PM' get var
     * @param bool $isExpand Link-wrapped input string
     * @return string
     * @access private
     */
    public function PMiconATagWrap($icon, $cmd, $isExpand = true)
    {
        if ($this->thisScript) {
            // Activate dynamic ajax-based tree
            $js = htmlspecialchars('Tree.load(' . GeneralUtility::quoteJSvalue($cmd) . ', ' . (int)$isExpand . ', this);');
            return '<a class="list-tree-control' . (!$isExpand ? ' list-tree-control-open' : ' list-tree-control-closed') . '" onclick="' . $js . '"><i class="fa"></i></a>';
        }
        return $icon;
    }

    /**
     * Will create and return the HTML code for a browsable tree
     * Is based on the mounts found in the internal array ->MOUNTS (set in the constructor)
     *
     * @return string HTML code for the browsable tree
     */
    public function getBrowsableTree()
    {
        // Get stored tree structure AND updating it if needed according to incoming PM GET var.
        $this->initializePositionSaving();
        // Init done:
        $treeArr = [];
        // Traverse mounts:
        $firstHtml = '';
        foreach ($this->MOUNTS as $idx => $uid) {
            // Set first:
            $this->bank = $idx;
            $isOpen = $this->stored[$idx][$uid] || $this->expandFirst || $uid === '0';
            // Save ids while resetting everything else.
            $curIds = $this->ids;
            $this->reset();
            $this->ids = $curIds;
            // Only, if not for uid 0
            if ($uid) {
                // Set PM icon for root of mount:
                $cmd = $this->bank . '_' . ($isOpen ? '0_' : '1_') . $uid . '_' . $this->treeName;
                $firstHtml = '<a class="list-tree-control list-tree-control-' . ($isOpen ? 'open' : 'closed')
                    . '" href="' . htmlspecialchars($this->getThisScript() . 'PM=' . $cmd) . '"><i class="fa"></i></a>';
            }
            // Preparing rootRec for the mount
            if ($uid) {
                $rootRec = $this->getRecord($uid);
                $firstHtml .= $this->getIcon($rootRec);
            } else {
                // Artificial record for the tree root, id=0
                $rootRec = $this->getRootRecord();
                $firstHtml .= $this->getRootIcon($rootRec);
            }
            if (is_array($rootRec)) {
                // In case it was swapped inside getRecord due to workspaces.
                $uid = $rootRec['uid'];
                // Add the root of the mount to ->tree
                $this->tree[] = ['HTML' => $firstHtml, 'row' => $rootRec, 'bank' => $this->bank, 'hasSub' => true, 'invertedDepth' => 1000];
                // If the mount is expanded, go down:
                if ($isOpen) {
                    // Set depth:
                    if ($this->addSelfId) {
                        $this->ids[] = $uid;
                    }
                    $this->getTree($uid);
                }
                // Add tree:
                $treeArr = array_merge($treeArr, $this->tree);
            }
        }
        return $this->printTree($treeArr);
    }
}
