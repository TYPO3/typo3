<?php
namespace TYPO3\CMS\Backend\Tree\View;

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
 * Class which generates the page tree
 *
 * Browsable tree, used in PagePositionMaps (move elements), Element Browser and RTE (for which it will be extended)
 * previously located inside typo3/class.browse_links.php
 */
class ElementBrowserPageTreeView extends BrowseTreeView {

	/**
	 * whether the page ID should be shown next to the title, activate through
	 * userTSconfig (options.pageTree.showPageIdWithTitle)
	 *
	 * @var bool
	 */
	public $ext_showPageId = FALSE;

	/**
	 * Constructor. Just calling init()
	 */
	public function __construct() {
		$this->determineScriptUrl();
		$this->init();
		$this->clause = ' AND doktype!=' . \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_RECYCLER . $this->clause;
	}

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param string $title Title, (must be ready for output, that means it must be htmlspecialchars()'ed).
	 * @param array $v The record
	 * @param bool $ext_pArrPages (Ignore)
	 * @return string Wrapping title string.
	 */
	public function wrapTitle($title, $v, $ext_pArrPages = '') {
		if ($this->ext_isLinkable($v['doktype'], $v['uid'])) {
			$aOnClick = 'return link_typo3Page(' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($v['uid']) . ');';
			return '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $title . '</a>';
		} else {
			return '<span style="color: #666666;">' . $title . '</span>';
		}
	}

	/**
	 * Create the page navigation tree in HTML
	 *
	 * @param array $treeArr Tree array
	 * @return string HTML output.
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
			$aOnClick = 'return jumpToUrl(' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($this->getThisScript() . 'act=' . $GLOBALS['SOBE']->browser->act . '&mode=' . $GLOBALS['SOBE']->browser->mode . '&expandPage=' . $v['row']['uid']) . ');';
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

	/**
	 * Returns TRUE if a doktype can be linked.
	 *
	 * @param int $doktype Doktype value to test
	 * @param int $uid uid to test.
	 * @return bool
	 */
	public function ext_isLinkable($doktype, $uid) {
		if ($uid && $doktype < 199) {
			return TRUE;
		}
	}

	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param string $icon HTML string to wrap, probably an image tag.
	 * @param string $cmd Command for 'PM' get var
	 * @param bool $bMark If set, the link will have a anchor point (=$bMark) and a name attribute (=$bMark)
	 * @return string Link-wrapped input string
	 */
	public function PM_ATagWrap($icon, $cmd, $bMark = '') {
		$name = '';
		if ($bMark) {
			$anchor = '#' . $bMark;
			$name = ' name=' . $bMark;
		}
		$aOnClick = 'return jumpToUrl(' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($this->getThisScript() . 'PM=' . $cmd) . ',' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($anchor) . ');';
		return '<a href="#"' . htmlspecialchars($name) . ' onclick="' . htmlspecialchars($aOnClick) . '">' . $icon . '</a>';
	}

	/**
	 * Wrapping the image tag, $icon, for the row, $row
	 *
	 * @param string $icon The image tag for the icon
	 * @param array $row The row for the current element
	 * @return string The processed icon input value.
	 */
	public function wrapIcon($icon, $row) {
		$content = $this->addTagAttributes($icon, ' title="id=' . $row['uid'] . '"');
		if ($this->ext_showPageId) {
			$content .= '[' . $row['uid'] . ']&nbsp;';
		}
		return $content;
	}

}
