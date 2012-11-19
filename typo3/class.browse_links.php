<?php
/*
 * @deprecated since 6.0, the classname TBE_browser_recordList and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/backend/Classes/RecordList/ElementBrowserRecordList.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('backend') . 'Classes/RecordList/ElementBrowserRecordList.php';
/**
 * Class which generates the page tree
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class localPageTree extends \TYPO3\CMS\Backend\Tree\View\BrowseTreeView {

	/**
	 * whether the page ID should be shown next to the title, activate through
	 * userTSconfig (options.pageTree.showPageIdWithTitle)
	 *
	 * @boolean
	 */
	public $ext_showPageId = FALSE;

	/**
	 * Constructor. Just calling init()
	 *
	 * @todo Define visibility
	 */
	public function __construct() {
		$this->thisScript = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_NAME');
		$this->init();
		$this->clause = ' AND doktype!=' . \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_RECYCLER . $this->clause;
	}

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param string $title Title, (must be ready for output, that means it must be htmlspecialchars()'ed).
	 * @param array $v The record
	 * @param boolean $ext_pArrPages (Ignore)
	 * @return string Wrapping title string.
	 * @todo Define visibility
	 */
	public function wrapTitle($title, $v, $ext_pArrPages = '') {
		if ($this->ext_isLinkable($v['doktype'], $v['uid'])) {
			$aOnClick = 'return link_typo3Page(\'' . $v['uid'] . '\');';
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
	 * @todo Define visibility
	 */
	public function printTree($treeArr = '') {
		$titleLen = intval($GLOBALS['BE_USER']->uc['titleLen']);
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
			$aOnClick = 'return jumpToUrl(\'' . $this->thisScript . '?act=' . $GLOBALS['SOBE']->browser->act . '&mode=' . $GLOBALS['SOBE']->browser->mode . '&expandPage=' . $v['row']['uid'] . '\');';
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
	 * @param integer $doktype Doktype value to test
	 * @param integer $uid uid to test.
	 * @return boolean
	 * @todo Define visibility
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
	 * @param boolean $bMark If set, the link will have a anchor point (=$bMark) and a name attribute (=$bMark)
	 * @return string Link-wrapped input string
	 * @todo Define visibility
	 */
	public function PM_ATagWrap($icon, $cmd, $bMark = '') {
		if ($bMark) {
			$anchor = '#' . $bMark;
			$name = ' name="' . $bMark . '"';
		}
		$aOnClick = 'return jumpToUrl(\'' . $this->thisScript . '?PM=' . $cmd . '\',\'' . $anchor . '\');';
		return '<a href="#"' . $name . ' onclick="' . htmlspecialchars($aOnClick) . '">' . $icon . '</a>';
	}

	/**
	 * Wrapping the image tag, $icon, for the row, $row
	 *
	 * @param string $icon The image tag for the icon
	 * @param array $row The row for the current element
	 * @return string The processed icon input value.
	 * @todo Define visibility
	 */
	public function wrapIcon($icon, $row) {
		$content = $this->addTagAttributes($icon, ' title="id=' . $row['uid'] . '"');
		if ($this->ext_showPageId) {
			$content .= '[' . $row['uid'] . ']&nbsp;';
		}
		return $content;
	}

}

/**
 * Page tree for the RTE - totally the same, no changes needed. (Just for the sake of beauty - or confusion... :-)
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class rtePageTree extends localPageTree {


}

/**
 * For TBE record browser
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class TBE_PageTree extends localPageTree {

	/**
	 * Returns TRUE if a doktype can be linked (which is always the case here).
	 *
	 * @param integer $doktype Doktype value to test
	 * @param integer $uid uid to test.
	 * @return boolean
	 * @todo Define visibility
	 */
	public function ext_isLinkable($doktype, $uid) {
		return TRUE;
	}

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param string $title Title, ready for output.
	 * @param array $v The record
	 * @param boolean $ext_pArrPages If set, pages clicked will return immediately, otherwise reload page.
	 * @return string Wrapping title string.
	 * @todo Define visibility
	 */
	public function wrapTitle($title, $v, $ext_pArrPages) {
		if ($ext_pArrPages) {
			$ficon = \TYPO3\CMS\Backend\Utility\IconUtility::getIcon('pages', $v);
			$onClick = 'return insertElement(\'pages\', \'' . $v['uid'] . '\', \'db\', ' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($v['title']) . ', \'\', \'\', \'' . $ficon . '\',\'\',1);';
		} else {
			$onClick = htmlspecialchars('return jumpToUrl(\'' . $this->thisScript . '?act=' . $GLOBALS['SOBE']->browser->act . '&mode=' . $GLOBALS['SOBE']->browser->mode . '&expandPage=' . $v['uid'] . '\');');
		}
		return '<a href="#" onclick="' . $onClick . '">' . $title . '</a>';
	}

}

/**
 * Base extension class which generates the folder tree.
 * Used directly by the RTE.
 * also used for the linkpicker on files
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class localFolderTree extends \TYPO3\CMS\Backend\Tree\View\FolderTreeView {

	/**
	 * @todo Define visibility
	 */
	public $ext_IconMode = 1;

	/**
	 * Initializes the script path
	 *
	 * @todo Define visibility
	 */
	public function __construct() {
		$this->thisScript = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_NAME');
		parent::__construct();
	}

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param string $title Title, ready for output.
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject The "record
	 * @return string Wrapping title string.
	 * @todo Define visibility
	 */
	public function wrapTitle($title, \TYPO3\CMS\Core\Resource\Folder $folderObject) {
		if ($this->ext_isLinkable($folderObject)) {
			$aOnClick = 'return jumpToUrl(\'' . $this->thisScript . '?act=' . $GLOBALS['SOBE']->browser->act . '&mode=' . $GLOBALS['SOBE']->browser->mode . '&expandFolder=' . rawurlencode($folderObject->getCombinedIdentifier()) . '\');';
			return '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $title . '</a>';
		} else {
			return '<span class="typo3-dimmed">' . $title . '</span>';
		}
	}

	/**
	 * Returns TRUE if the input "record" contains a folder which can be linked.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject Object with information about the folder element. Contains keys like title, uid, path, _title
	 * @return boolean TRUE is returned if the path is found in the web-part of the server and is NOT a recycler or temp folder
	 * @todo Define visibility
	 */
	public function ext_isLinkable(\TYPO3\CMS\Core\Resource\Folder $folderObject) {
		if (!$folderObject->getStorage()->isPublic() || strstr($folderObject->getIdentifier(), '_recycler_') || strstr($folderObject->getIdentifier(), '_temp_')) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param string $icon HTML string to wrap, probably an image tag.
	 * @param string $cmd Command for 'PM' get var
	 * @param boolean $bMark If set, the link will have a anchor point (=$bMark) and a name attribute (=$bMark)
	 * @return string Link-wrapped input string
	 * @access private
	 * @todo Define visibility
	 */
	public function PM_ATagWrap($icon, $cmd, $bMark = '') {
		if ($bMark) {
			$anchor = '#' . $bMark;
			$name = ' name="' . $bMark . '"';
		}
		$aOnClick = 'return jumpToUrl(\'' . $this->thisScript . '?PM=' . $cmd . '\',\'' . $anchor . '\');';
		return '<a href="#"' . $name . ' onclick="' . htmlspecialchars($aOnClick) . '">' . $icon . '</a>';
	}

}

/**
 * Folder tree for the RTE - totally the same, no changes needed. (Just for the sake of beauty - or confusion... :-)
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class rteFolderTree extends localFolderTree {


}

/**
 * For TBE File Browser
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class TBE_FolderTree extends localFolderTree {

	// If file-drag mode is set, temp and recycler folders are filtered out.
	/**
	 * @todo Define visibility
	 */
	public $ext_noTempRecyclerDirs = 0;

	/**
	 * Returns TRUE if the input "record" contains a folder which can be linked.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject object with information about the folder element. Contains keys like title, uid, path, _title
	 * @return boolean TRUE is returned if the path is NOT a recycler or temp folder AND if ->ext_noTempRecyclerDirs is not set.
	 * @todo Define visibility
	 */
	public function ext_isLinkable($folderObject) {
		if ($this->ext_noTempRecyclerDirs && (substr($folderObject->getIdentifier(), -7) == '_temp_/' || substr($folderObject->getIdentifier(), -11) == '_recycler_/')) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param string $title Title, ready for output.
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject The folderObject 'record'
	 * @return string Wrapping title string.
	 * @todo Define visibility
	 */
	public function wrapTitle($title, $folderObject) {
		if ($this->ext_isLinkable($folderObject)) {
			$aOnClick = 'return jumpToUrl(\'' . $this->thisScript . '?act=' . $GLOBALS['SOBE']->browser->act . '&mode=' . $GLOBALS['SOBE']->browser->mode . '&expandFolder=' . rawurlencode($folderObject->getCombinedIdentifier()) . '\');';
			return '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $title . '</a>';
		} else {
			return '<span class="typo3-dimmed">' . $title . '</span>';
		}
	}

}

/*
 * @deprecated since 6.0, the classname browse_links and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/recordlist/Classes/Browser/ElementBrowser.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('recordlist') . 'Classes/Browser/ElementBrowser.php';
?>