<?php
namespace TYPO3\CMS\Rtehtmlarea;

/**
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
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author 	Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class which generates the page tree
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class PageTree extends \localPageTree {

	/**
	 * Create the page navigation tree in HTML
	 *
	 * @param 	array		Tree array
	 * @return 	string		HTML output.
	 * @todo Define visibility
	 */
	public function printTree($treeArr = '') {
		$titleLen = (int)$GLOBALS['BE_USER']->uc['titleLen'];
		if (!is_array($treeArr)) {
			$treeArr = $this->tree;
		}
		$out = '';
		$c = 0;
		foreach ($treeArr as $k => $v) {
			$c++;
			$bgColorClass = ($c + 1) % 2 ? 'bgColor' : 'bgColor-10';
			if ($GLOBALS['SOBE']->browser->curUrlInfo['act'] == 'page' && $GLOBALS['SOBE']->browser->curUrlInfo['pageid'] == $v['row']['uid'] && $GLOBALS['SOBE']->browser->curUrlInfo['pageid']) {
				$arrCol = '<td><img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/blinkarrow_right.gif', 'width="5" height="9"') . ' class="c-blinkArrowR" alt="" /></td>';
				$bgColorClass = 'bgColor4';
			} else {
				$arrCol = '<td></td>';
			}
			$aOnClick = 'return jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript() . 'act=' . $GLOBALS['SOBE']->browser->act . '&editorNo=' . $GLOBALS['SOBE']->browser->editorNo . '&contentTypo3Language=' . $GLOBALS['SOBE']->browser->contentTypo3Language . '&mode=' . $GLOBALS['SOBE']->browser->mode . '&expandPage=' . $v['row']['uid']) . ');';
			$cEbullet = $this->ext_isLinkable($v['row']['doktype'], $v['row']['uid']) ? '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '"><img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/ol/arrowbullet.gif', 'width="18" height="16"') . ' alt="" /></a>' : '';
			$out .= '
				<tr class="' . $bgColorClass . '">
					<td nowrap="nowrap"' . ($v['row']['_CSSCLASS'] ? ' class="' . $v['row']['_CSSCLASS'] . '"' : '') . '>' . $v['HTML'] . $this->wrapTitle($this->getTitleStr($v['row'], $titleLen), $v['row'], $this->ext_pArrPages) . '</td>' . $arrCol . '<td>' . $cEbullet . '</td>
				</tr>';
		}
		$out = '


			<!--
				Navigation Page Tree:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-tree">
				' . $out . '
			</table>';
		return $out;
	}

}
