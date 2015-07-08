<?php
namespace TYPO3\CMS\Rtehtmlarea;

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

/**
 * Displays the page/file tree for browsing database records or files.
 * Used from TCEFORMS an other elements
 * In other words: This is the ELEMENT BROWSER!
 *
 * Adapted for htmlArea RTE by Stanislas Rolland
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class which generates the page tree
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class PageTree extends \TYPO3\CMS\Backend\Tree\View\ElementBrowserPageTreeView {

	/**
	 * Create the page navigation tree in HTML
	 *
	 * @param array Tree array
	 * @return string HTML output.
	 */
	public function printTree($treeArr = '') {
		$titleLen = (int)$GLOBALS['BE_USER']->uc['titleLen'];
		if (!is_array($treeArr)) {
			$treeArr = $this->tree;
		}
		$out = '';
		$closeDepth = array();
		foreach ($treeArr as $treeItem) {

			$classAttr = $treeItem['row']['_CSSCLASS'];
			if ($treeItem['isFirst']) {
				$out .= '<ul class="list-tree">';
			}

			// Add CSS classes to the list item
			if ($treeItem['hasSub']) {
				$classAttr .= ' list-tree-control-open';
			}

			if ($GLOBALS['SOBE']->browser->curUrlInfo['act'] == 'page' && $GLOBALS['SOBE']->browser->curUrlInfo['pageid'] == $treeItem['row']['uid'] && $GLOBALS['SOBE']->browser->curUrlInfo['pageid']) {
				$arrCol = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/blinkarrow_right.gif', 'width="5" height="9"') . ' class="c-blinkArrowR pull-right" alt="" />';
			} else {
				$arrCol = '';
			}
			$aOnClick = 'return jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript() . 'act=' . $GLOBALS['SOBE']->browser->act . '&editorNo=' . $GLOBALS['SOBE']->browser->editorNo . '&contentTypo3Language=' . $GLOBALS['SOBE']->browser->contentTypo3Language . '&mode=' . $GLOBALS['SOBE']->browser->mode . '&expandPage=' . $treeItem['row']['uid']) . ');';
			$cEbullet = $this->ext_isLinkable($treeItem['row']['doktype'], $treeItem['row']['uid']) ? '<a href="#" class="pull-right" onclick="' . htmlspecialchars($aOnClick) . '"><img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/ol/arrowbullet.gif', 'width="18" height="16"') . ' alt="" /></a>' : '';
			$out .= '<li' . ($classAttr ? ' class="' . trim($classAttr) . '"' : '') . '><span class="list-tree-group">' . $treeItem['HTML'] . $this->wrapTitle($this->getTitleStr($treeItem['row'], $titleLen), $treeItem['row'], $this->ext_pArrPages) . $cEbullet . $arrCol . '</span>';

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
		$out = '<ul class="list-tree" id="treeRoot">' . $out . '</ul>';
		return $out;
	}

}
