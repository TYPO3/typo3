<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Recordlist\Tree\View;

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

use TYPO3\CMS\Backend\Tree\View\ElementBrowserPageTreeView;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * Specific page tree for the record link handler.
 * @internal
 */
class RecordBrowserPageTreeView extends ElementBrowserPageTreeView
{
    /**
     * Creates the page navigation tree in HTML.
     *
     * This variant does not show the right-pointing caret next to pages and puts the "action" link
     * directly on each page title.
     *
     * @param array|string $treeArr Tree array
     * @return string HTML output.
     */
    public function printTree($treeArr = '')
    {
        $titleLen = (int)$GLOBALS['BE_USER']->uc['titleLen'];
        if (!is_array($treeArr)) {
            $treeArr = $this->tree;
        }
        $out = '';
        // We need to count the opened <ul>'s every time we dig into another level,
        // so we know how many we have to close when all children are done rendering
        $closeDepth = [];
        foreach ($treeArr as $treeItem) {
            $classAttr = $treeItem['row']['_CSSCLASS'];
            if ($treeItem['isFirst']) {
                $out .= '<ul class="list-tree">';
            }

            // Add CSS classes to the list item
            if ($treeItem['hasSub']) {
                $classAttr .= ' list-tree-control-open';
            }

            $selected = '';
            if ($this->linkParameterProvider->isCurrentlySelectedItem(['pid' => (int)$treeItem['row']['uid']])) {
                $selected = ' bg-success';
                $classAttr .= ' active';
            }
            $out .= '
				<li' . ($classAttr ? ' class="' . trim($classAttr) . '"' : '') . '>
					<span class="list-tree-group' . $selected . '">
						' . $treeItem['HTML']
                    . $this->wrapTitle($this->getTitleStr($treeItem['row'], $titleLen), $treeItem['row'], $this->ext_pArrPages)
                    . '</span>
				';
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
        $out = '<ul class="list-tree list-tree-root">' . $out . '</ul>';

        return $out;
    }

    /**
     * Wrapping the title in a link, if applicable.
     *
     * @param string $title Title, (must be ready for output, that means it must be htmlspecialchars()'ed).
     * @param array $record The record
     * @param bool $ext_pArrPages (ignored)
     * @return string Wrapping title string.
     */
    public function wrapTitle($title, $record, $ext_pArrPages = false)
    {
        $urlParameters = $this->linkParameterProvider->getUrlParameters(['pid' => (int)$record['uid']]);
        $url = $this->getThisScript() . HttpUtility::buildQueryString($urlParameters);
        $aOnClick = 'return jumpToUrl(' . GeneralUtility::quoteJSvalue($url) . ');';

        return '<span class="list-tree-title"><a href="#" onclick="' . htmlspecialchars($aOnClick) . '">'
            . $title . '</a></span>';
    }

    /**
     * Returns TRUE if a doktype can be linked.
     *
     * In this case, all pages can be linked.
     *
     * @param int $doktype Doktype value to test
     * @param int $uid uid to test.
     * @return bool
     */
    public function ext_isLinkable($doktype, $uid)
    {
        return true;
    }
}
