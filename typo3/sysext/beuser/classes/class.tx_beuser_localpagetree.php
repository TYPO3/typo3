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
 * Base Extension class for printing a page tree (non-browsable though)
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_beuser
 */
class tx_beuser_localPageTree extends t3lib_browseTree {
	var $expandFirst=0;
	var $expandAll=0;

	/**
	 * Local backend user (not the GLOBALS[] backend user!!)
	 *
	 * @var t3lib_beUserAuth
	 */
	var $BE_USER;

	/**
	 * Constructor for the local page tree.
	 *
	 * @param	object		Local backend user (not the GLOBALS[] backend user!!)
	 * @param	array		Webmounts for the backend user.
	 * @return	void
	 */
	function __construct($BE_USER, $WEBMOUNTS='') {
		$this->init();

		$this->BE_USER = $BE_USER;
		$this->MOUNTS = $WEBMOUNTS;
		$this->clause = $this->ext_permsC();	// Notice, this clause does NOT filter out un-readable pages. This is the POINT since this class is ONLY used for the main overview where ALL is shown! Otherwise "AND '.$this->BE_USER->getPagePermsClause(1).'" should be added.
		$this->orderByFields = 'sorting';
	}

	/**
	 * Return select permissions.
	 *
	 * @return	string		WHERE query part.
	 */
	function ext_permsC() {
		return '';
	}

	/**
	 * Wraps the title.
	 *
	 * @param	string		[See parent]
	 * @param	array		[See parent]
	 * @return	string
	 */
	function wrapTitle($str, $row) {
		return $str;
	}

	/**
	 * Wraps the plus/minus icon - in this case we just return blank which means we STRIP AWAY the plus/minus icon!
	 *
	 * @param	string		[See parent]
	 * @param	string		[See parent]
	 * @param	string		[See parent]
	 * @return	string
	 */
	function PM_ATagWrap($icon, $cmd, $bMark = '') {
		return '';
	}

	/**
	 * Wrapping the icon of the element/page. Normally a click menu is wrapped around the icon, but in this case only a title parameter is set.
	 *
	 * @param	string		Icon image tag.
	 * @param	array		Row.
	 * @return	string		Icon with title attribute added.
	 */
	function wrapIcon($icon, $row) {
		// Add title attribute to input icon tag
		$title = '['.$row['uid'].'] '.t3lib_BEfunc::getRecordPath($row['uid'], '', 15);
		$theIcon = $this->addTagAttributes($icon, ($this->titleAttrib ? $this->titleAttrib.'="'.htmlspecialchars($title).'"' : '').' border="0"');

		return $theIcon;
	}

	/**
	 * This will make sure that no position data is acquired from the BE_USER uc variable.
	 *
	 * @return	void
	 */
	function initializePositionSaving() {
		$this->stored=array();
	}
}
?>