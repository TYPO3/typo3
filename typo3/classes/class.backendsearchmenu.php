<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Ingo Renner <ingo@typo3.org>
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
 * class to render the backend search toolbar item menu
 *
 * $Id$
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage core
 */
class BackendSearchMenu implements backend_toolbarItem {

	/**
	 * reference back to the backend object
	 *
	 * @var	TYPO3backend
	 */
	protected $backendReference;

	/**
	 * constructor
	 *
	 * @param	TYPO3backend	TYPO3 backend object reference
	 */
	public function __construct(TYPO3backend &$backendReference = null) {
		$this->backendReference = $backendReference;
	}

	/**
	 * checks whether the user has access to this toolbar item
	 *
	 * @return  boolean  true if user has access, false if not
	 */
	public function checkAccess() {
			// Backendsearch module is enabled for everybody
		return true;
	}

	/**
	 * Creates the selector for workspaces
	 *
	 * @return	string		workspace selector as HTML select
	 */
	public function render() {
		$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:toolbarItems.search', true);
		$this->addJavascriptToBackend();
		$searchMenu = array();

		$searchMenu[] = '<a href="#" class="toolbar-item">' .
			t3lib_iconWorks::getSpriteIcon('apps-toolbar-menu-search', array('title' => $title)) .
			'</a>';

		$searchMenu[] = '<div class="toolbar-item-menu" style="display: none;">';
		$searchMenu[] = '<input type="text" id="search-query" name="search-query" value="" />';
		$searchMenu[] = '</div>';

		return implode(LF, $searchMenu);
	}

	/**
	 * adds the necessary JavaScript to the backend
	 *
	 * @return	void
	 */
	protected function addJavascriptToBackend() {
		$this->backendReference->addJavascriptFile('js/backendsearch.js');
	}

	/**
	 * returns additional attributes for the list item in the toolbar
	 *
	 * @return	string		list item HTML attibutes
	 */
	public function getAdditionalAttributes() {
		return ' id="backend-search-menu"';
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.backendsearchmenu.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.backendsearchmenu.php']);
}

?>