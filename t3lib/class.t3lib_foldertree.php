<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Generate a folder tree
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 */


/**
 * Extension class for the t3lib_treeView class, specially made for browsing folders in the File module
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 * @package TYPO3
 * @subpackage t3lib
 * @see class t3lib_treeView
 */
class t3lib_folderTree extends t3lib_treeView {

	/**
	 * Constructor function of the class
	 *
	 * @return	void
	 */
	function __construct() {
		parent::init();

		$this->MOUNTS = $GLOBALS['FILEMOUNTS'];

		$this->treeName = 'folder';
		$this->titleAttrib = ''; //don't apply any title
		$this->domIdPrefix = 'folder';
	}

	/**
	 * Wrapping the folder icon
	 *
	 * @param	string		The image tag for the icon
	 * @param	array		The row for the current element
	 * @return	string		The processed icon input value.
	 * @access private
	 */
	function wrapIcon($icon, $row) {
			// Add title attribute to input icon tag
		$theFolderIcon = $this->addTagAttributes($icon, ($this->titleAttrib ? $this->titleAttrib . '="' . $this->getTitleAttrib($row) . '"' : ''));

			// Wrap icon in click-menu link.
		if (!$this->ext_IconMode) {
			$theFolderIcon = $GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon($theFolderIcon, $row['path'], '', 0);
		} elseif (!strcmp($this->ext_IconMode, 'titlelink')) {
			$aOnClick = 'return jumpTo(\'' . $this->getJumpToParam($row) . '\',this,\'' . $this->domIdPrefix . $this->getId($row) . '\',' . $this->bank . ');';
			$theFolderIcon = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $theFolderIcon . '</a>';
		}
		return $theFolderIcon;
	}

	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param	string		Title string
	 * @param	string		Item record
	 * @param	integer		Bank pointer (which mount point number)
	 * @return	string
	 * @access private
	 */
	function wrapTitle($title, $row, $bank = 0) {
		$aOnClick = 'return jumpTo(\'' . $this->getJumpToParam($row) . '\',this,\'' . $this->domIdPrefix . $this->getId($row) . '\',' . $bank . ');';
		$CSM = '';
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['useOnContextMenuHandler']) {
			$CSM = ' oncontextmenu="' . htmlspecialchars($GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon('', $row['path'], '', 0, '', '', TRUE)) . '"';
		}
		return '<a href="#" title="' . htmlspecialchars($row['title']) . '" onclick="' . htmlspecialchars($aOnClick) . '"' . $CSM . '>' . $title . '</a>';
	}

	/**
	 * Returns the id from the record - for folders, this is an md5 hash.
	 *
	 * @param	array		Record array
	 * @return	integer		The "uid" field value.
	 */
	function getId($v) {
		return t3lib_div::md5Int($v['path']);
	}

	/**
	 * Returns jump-url parameter value.
	 *
	 * @param	array		The record array.
	 * @return	string		The jump-url parameter.
	 */
	function getJumpToParam($v) {
		return rawurlencode($v['path']);
	}

	/**
	 * Returns the title for the input record. If blank, a "no title" labele (localized) will be returned.
	 * '_title' is used for setting an alternative title for folders.
	 *
	 * @param	array		The input row array (where the key "_title" is used for the title)
	 * @param	integer		Title length (30)
	 * @return	string		The title.
	 */
	function getTitleStr($row, $titleLen = 30) {
		return $row['_title'] ? $row['_title'] : parent::getTitleStr($row, $titleLen);
	}

	/**
	 * Will create and return the HTML code for a browsable tree of folders.
	 * Is based on the mounts found in the internal array ->MOUNTS (set in the constructor)
	 *
	 * @return	string		HTML code for the browsable tree
	 */
	function getBrowsableTree() {

			// Get stored tree structure AND updating it if needed according to incoming PM GET var.
		$this->initializePositionSaving();

			// Init done:
		$titleLen = intval($this->BE_USER->uc['titleLen']);
		$treeArr = array();

			// Traverse mounts:
		foreach ($this->MOUNTS as $key => $val) {
			$md5_uid = md5($val['path']);
			$specUID = hexdec(substr($md5_uid, 0, 6));
			$this->specUIDmap[$specUID] = $val['path'];

				// Set first:
			$this->bank = $val['nkey'];
			$isOpen = $this->stored[$val['nkey']][$specUID] || $this->expandFirst;
			$this->reset();

				// Set PM icon:
			$cmd = $this->bank . '_' . ($isOpen ? '0_' : '1_') . $specUID . '_' . $this->treeName;
			$icon = '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/ol/' . ($isOpen ? 'minus' : 'plus') . 'only.gif', 'width="18" height="16"') . ' alt="" />';
			$firstHtml = $this->PM_ATagWrap($icon, $cmd);

			switch ($val['type']) {
				case 'user':
					$icon = 'gfx/i/_icon_ftp_user.gif';
				break;
				case 'group':
					$icon = 'gfx/i/_icon_ftp_group.gif';
				break;
				case 'readonly':
					$icon = 'gfx/i/_icon_ftp_readonly.gif';
				break;
				default:
					$icon = 'gfx/i/_icon_ftp.gif';
				break;
			}

				// Preparing rootRec for the mount
			$firstHtml .= $this->wrapIcon('<img' . t3lib_iconWorks::skinImg($this->backPath, $icon, 'width="18" height="16"') . ' alt="" />', $val);
			$row = array();
			$row['path'] = $val['path'];
			$row['uid'] = $specUID;
			$row['title'] = $val['name'];

				// Add the root of the mount to ->tree
			$this->tree[] = array('HTML' => $firstHtml, 'row' => $row, 'bank' => $this->bank);

				// If the mount is expanded, go down:
			if ($isOpen) {
					// Set depth:
				$depthD = '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/ol/blank.gif', 'width="18" height="16"') . ' alt="" />';
				$this->getFolderTree($val['path'], 999, $depthD, $val['type']);
			}

				// Add tree:
			$treeArr = array_merge($treeArr, $this->tree);
		}
		return $this->printTree($treeArr);
	}

	/**
	 * Fetches the data for the tree
	 *
	 * @param	string		Abs file path
	 * @param	integer		Max depth (recursivity limit)
	 * @param	string		HTML-code prefix for recursive calls.
	 * @return	integer		The count of items on the level
	 * @see getBrowsableTree()
	 */
	function getFolderTree($files_path, $depth = 999, $depthData = '', $type = '') {

			// This generates the directory tree
		$dirs = t3lib_div::get_dirs($files_path);

		$c = 0;
		if (is_array($dirs)) {
			$depth = intval($depth);
			$HTML = '';
			$a = 0;
			$c = count($dirs);
			sort($dirs);

			foreach ($dirs as $key => $val) {
				$a++;
				$this->tree[] = array(); // Reserve space.
				end($this->tree);
				$treeKey = key($this->tree); // Get the key for this space
				$LN = ($a == $c) ? 'blank' : 'line';

				$val = preg_replace('/^\.\//', '', $val);
				$title = $val;
				$path = $files_path . $val . '/';
				$webpath = t3lib_BEfunc::getPathType_web_nonweb($path);

				$md5_uid = md5($path);
				$specUID = hexdec(substr($md5_uid, 0, 6));
				$this->specUIDmap[$specUID] = $path;
				$row = array();
				$row['path'] = $path;
				$row['uid'] = $specUID;
				$row['title'] = $title;

				if ($depth > 1 && $this->expandNext($specUID)) {
					$nextCount = $this->getFolderTree(
						$path,
							$depth - 1,
						$this->makeHTML ? $depthData . '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/ol/' . $LN . '.gif', 'width="18" height="16"') . ' alt="" />' : '',
						$type
					);
					$exp = 1; // Set "did expand" flag
				} else {
					$nextCount = $this->getCount($path);
					$exp = 0; // Clear "did expand" flag
				}

					// Set HTML-icons, if any:
				if ($this->makeHTML) {
					$HTML = $depthData . $this->PMicon($row, $a, $c, $nextCount, $exp);

					$icon = 'gfx/i/_icon_' . $webpath . 'folders' . ($type == 'readonly' ? '_ro' : '') . '.gif';
					if ($val == '_temp_') {
						$icon = 'gfx/i/sysf.gif';
						$row['title'] = $GLOBALS['LANG']->sl('LLL:EXT:lang/locallang_mod_file_list.xml:temp', TRUE);
						$row['_title'] = '<strong>' . $GLOBALS['LANG']->sl('LLL:EXT:lang/locallang_mod_file_list.xml:temp', TRUE) . '</strong>';
					}
					if ($val == '_recycler_') {
						$icon = 'gfx/i/recycler.gif';
						$row['title'] = $GLOBALS['LANG']->sl('LLL:EXT:lang/locallang_mod_file_list.xml:recycler', TRUE);
						$row['_title'] = '<strong>' . $GLOBALS['LANG']->sl('LLL:EXT:lang/locallang_mod_file_list.xml:recycler', TRUE) . '</strong>';
					}
					$HTML .= $this->wrapIcon('<img' . t3lib_iconWorks::skinImg($this->backPath, $icon, 'width="18" height="16"') . ' alt="" />', $row);
				}

					// Finally, add the row/HTML content to the ->tree array in the reserved key.
				$this->tree[$treeKey] = Array(
					'row' => $row,
					'HTML' => $HTML,
					'bank' => $this->bank
				);
			}
		}
		return $c;
	}

	/**
	 * Counts the number of directories in a file path.
	 *
	 * @param	string		File path.
	 * @return	integer
	 */
	function getCount($files_path) {
			// This generates the directory tree
		$dirs = t3lib_div::get_dirs($files_path);
		$c = 0;
		if (is_array($dirs)) {
			$c = count($dirs);
		}
		return $c;
	}

	/**
	 * Get stored tree structure AND updating it if needed according to incoming PM GET var.
	 *
	 * @return	void
	 * @access private
	 */
	function initializePositionSaving() {
			// Get stored tree structure:
		$this->stored = unserialize($this->BE_USER->uc['browseTrees'][$this->treeName]);

			// Mapping md5-hash to shorter number:
		$hashMap = array();
		foreach ($this->MOUNTS as $key => $val) {
			$nkey = hexdec(substr($key, 0, 4));
			$hashMap[$nkey] = $key;
			$this->MOUNTS[$key]['nkey'] = $nkey;
		}

			// PM action:
			// (If an plus/minus icon has been clicked, the PM GET var is sent and we must update the stored positions in the tree):
		$PM = explode('_', t3lib_div::_GP('PM')); // 0: mount key, 1: set/clear boolean, 2: item ID (cannot contain "_"), 3: treeName
		if (count($PM) == 4 && $PM[3] == $this->treeName) {
			if (isset($this->MOUNTS[$hashMap[$PM[0]]])) {
				if ($PM[1]) { // set
					$this->stored[$PM[0]][$PM[2]] = 1;
					$this->savePosition($this->treeName);
				} else { // clear
					unset($this->stored[$PM[0]][$PM[2]]);
					$this->savePosition($this->treeName);
				}
			}
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_foldertree.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_foldertree.php']);
}

?>