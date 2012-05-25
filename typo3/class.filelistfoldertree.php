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
 * Folder navigation tree for the File main module
 *
 * @author	Benjamin Mack   <bmack@xnos.org>
 *
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   71: class fileListTree extends t3lib_browseTree
 *   81:     function webPageTree()
 *   92:     function wrapIcon($icon, &$row)
 *  130:     function wrapStop($str, $row)
 *  146:     function wrapTitle($title, $row, $bank=0)
 *  165:     function printTree($treeItems = '')
 *  271:     function PMicon($row, $a, $c, $nextCount, $exp)
 *  292:     function PMiconATagWrap($icon, $cmd, $isExpand = TRUE)
 *  309:     function getBrowsableTree()
 *  377:     function getTree($uid, $depth=999, $depthData='', $blankLineCode='', $subCSSclass='')
 *
 *
 * TOTAL FUNCTIONS: 9
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
/**
 * Extension class for the t3lib_filetree class, needed for drag and drop and ajax functionality
 *
 * @author	Sebastian Kurfürst <sebastian@garbage-group.de>
 * @author	Benjamin Mack   <bmack@xnos.org>
 * @package TYPO3
 * @subpackage core
 * @see class t3lib_browseTree
 */
class filelistFolderTree extends t3lib_folderTree {

	var $ext_IconMode;

	/**
	 * Wrapping icon in browse tree
	 *
	 * @param string $theFolderIcon Icon IMG code
	 * @param t3lib_file_Folder $folderObject the folder object
	 * @return string folder icon
	 */
	function wrapIcon($theFolderIcon, t3lib_file_Folder $folderObject) {
		$theFolderIcon = parent::wrapIcon($theFolderIcon, $folderObject);
			// Wrap icon in a drag/drop span.
		return '<span class="dragIcon" id="dragIconID_' . $this->getJumpToParam($folderObject) . '">' . $theFolderIcon . '</span>';
	}

	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param string $title Title string
	 * @param t3lib_file_Folder $folderObject Folder to work on
	 * @param integer $bank Bank pointer (which mount point number)
	 * @return string
	 * @access private
	 */
	function wrapTitle($title, t3lib_file_Folder $folderObject, $bank = 0) {
		$theFolderTitle = parent::wrapTitle($title, $folderObject, $bank);

			// Wrap title in a drag/drop span.
		return '<span class="dragTitle" id="dragTitleID_' . $this->getJumpToParam($folderObject) . '">' . $theFolderTitle . '</span>';
	}
}
?>