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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

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
	 * @var bool
	 */
	public $ext_showNavTitle = FALSE;

	/**
	 * @var bool
	 */
	public $ext_pArrPages = TRUE;

	/**
	 * Constructor. Just calling init()
	 */
	public function __construct() {
		$this->determineScriptUrl();
		$this->init();
		$this->clause = ' AND doktype != ' . PageRepository::DOKTYPE_RECYCLER . $this->clause;
	}

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param string $title Title, (must be ready for output, that means it must be htmlspecialchars()'ed).
	 * @param array $v The record
	 * @param bool $ext_pArrPages (ignored)
	 * @return string Wrapping title string.
	 */
	public function wrapTitle($title, $v, $ext_pArrPages = FALSE) {
		if ($this->ext_isLinkable($v['doktype'], $v['uid'])) {
			$aOnClick = 'return link_typo3Page(' . GeneralUtility::quoteJSvalue($v['uid']) . ');';
			return '<span class="list-tree-title"><a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $title . '</a></span>';
		} else {
			return '<span class="list-tree-title text-muted">' . $title . '</span>';
		}
	}

	/**
	 * Create the page navigation tree in HTML
	 *
	 * @param array|string $treeArr Tree array
	 * @return string HTML output.
	 */
	public function printTree($treeArr = '') {
		$titleLen = (int)$GLOBALS['BE_USER']->uc['titleLen'];
		if (!is_array($treeArr)) {
			$treeArr = $this->tree;
		}
		$out = '';
		// We need to count the opened <ul>'s every time we dig into another level,
		// so we know how many we have to close when all children are done rendering
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

			$selected = '';
			if ($GLOBALS['SOBE']->browser->curUrlInfo['act'] == 'page' && $GLOBALS['SOBE']->browser->curUrlInfo['pageid'] == $treeItem['row']['uid'] && $GLOBALS['SOBE']->browser->curUrlInfo['pageid']) {
				$selected = ' bg-success';
				$classAttr .= ' active';
			}
			$aOnClick = 'return jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript() . 'act=' . $GLOBALS['SOBE']->browser->act . '&mode=' . $GLOBALS['SOBE']->browser->mode . '&expandPage=' . $treeItem['row']['uid']) . ');';
			$cEbullet = $this->ext_isLinkable($treeItem['row']['doktype'], $treeItem['row']['uid']) ? '<a href="#" class="list-tree-show" onclick="' . htmlspecialchars($aOnClick) . '"><i class="fa fa-caret-square-o-right"></i></a>' : '';
			$out .= '
				<li' . ($classAttr ? ' class="' . trim($classAttr) . '"' : '') . '>
					<span class="list-tree-group' . $selected . '">
						' . $cEbullet . $treeItem['HTML'] . $this->wrapTitle($this->getTitleStr($treeItem['row'], $titleLen), $treeItem['row'], $this->ext_pArrPages) . '
					</span>
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
	 * Returns TRUE if a doktype can be linked.
	 *
	 * @param int $doktype Doktype value to test
	 * @param int $uid uid to test.
	 * @return bool
	 */
	public function ext_isLinkable($doktype, $uid) {
		return $uid && $doktype < PageRepository::DOKTYPE_SPACER;
	}

	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param string $icon HTML string to wrap, probably an image tag.
	 * @param string $cmd Command for 'PM' get var
	 * @param string $bMark If set, the link will have an anchor point (=$bMark) and a name attribute (=$bMark)
	 * @param bool $isOpen
	 * @return string Link-wrapped input string
	 */
	public function PM_ATagWrap($icon, $cmd, $bMark = '', $isOpen = FALSE) {
		$anchor = $bMark ? '#' . $bMark : '';
		$name = $bMark ? ' name=' . $bMark : '';
		$aOnClick = 'return jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript() . 'PM=' . $cmd) . ',' . GeneralUtility::quoteJSvalue($anchor) . ');';
		return '<a class="list-tree-control ' . ($isOpen ? 'list-tree-control-open' : 'list-tree-control-closed')
			. '" href="#"' . htmlspecialchars($name) . ' onclick="' . htmlspecialchars($aOnClick) . '"><i class="fa"></i></a>';
	}

	/**
	 * Wrapping the image tag, $icon, for the row, $row
	 *
	 * @param string $icon The image tag for the icon
	 * @param array $row The row for the current element
	 * @return string The processed icon input value.
	 */
	public function wrapIcon($icon, $row) {
		if ($this->ext_showPageId) {
			$icon .= '[' . $row['uid'] . ']&nbsp;';
		}
		return $icon;
	}

}
