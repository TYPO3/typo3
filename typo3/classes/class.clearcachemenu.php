<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Ingo Renner <ingo@typo3.org>
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
 * class to render the menu for the cache clearing actions
 *
 * $Id$
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage core
 */
class ClearCacheMenu implements backend_toolbarItem {

	public $cacheActions;

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
		$this->cacheActions     = array();

			// Clear cache for ALL tables!
		if($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.all')) {
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:rm.clearCacheMenu_all');
			$this->cacheActions[] = array(
				'id'    => 'all',
				'title' => $title,
				'href'  => $this->backPath.'tce_db.php?vC='.$GLOBALS['BE_USER']->veriCode().'&cacheCmd=all',
				'icon'  => '<img'.t3lib_iconWorks::skinImg($this->backPath, 'gfx/lightning_red.png', 'width="16" height="16"').' title="'.htmlspecialchars($title).'" alt="" />'
			);
		}

			// Clear cache for either ALL pages
		if($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.pages')) {
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:rm.clearCacheMenu_pages');
			$this->cacheActions[] = array(
				'id'    => 'pages',
				'title' => $title,
				'href'  => $this->backPath.'tce_db.php?vC='.$GLOBALS['BE_USER']->veriCode().'&cacheCmd=pages',
				'icon'  => '<img'.t3lib_iconWorks::skinImg($this->backPath, 'gfx/lightning.png', 'width="16" height="16"').' title="'.htmlspecialchars($title).'" alt="" />'
			);
		}

			// Clearing of cache-files in typo3conf/ + menu
		if($GLOBALS['BE_USER']->isAdmin() && $GLOBALS['TYPO3_CONF_VARS']['EXT']['extCache']) {
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:rm.clearCacheMenu_allTypo3Conf');
			$this->cacheActions[] = array(
				'id'    => 'temp_CACHED',
				'title' => $title,
				'href'  => $this->backPath.'tce_db.php?vC='.$GLOBALS['BE_USER']->veriCode().'&cacheCmd=temp_CACHED',
				'icon'  => '<img'.t3lib_iconWorks::skinImg($this->backPath, 'gfx/lightning_green.png', 'width="16" height="16"').' title="'.htmlspecialchars($title).'" alt="" />'
			);
		}

	}

	/**
	 * checks whether the user has access to this toolbar item
	 *
	 * @return  boolean  true if user has access, false if not
	 */
	public function checkAccess() {
		return (
			$GLOBALS['BE_USER']->isAdmin()
			|| $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.pages')
			|| $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.all')
		);
	}

	/**
	 * Creates the selector for workspaces
	 *
	 * @return	string		workspace selector as HTML select
	 */
	public function render() {
		$this->addJavascriptToBackend();
		$cacheMenu = array();

		$cacheMenu[] = '<a href="#" class="toolbar-item"><img'.t3lib_iconWorks::skinImg($this->backPath, 'gfx/lightning.png', 'width="16" height="16"').' title="Clear Cache" alt="" /></a>';

		$cacheMenu[] = '<ul class="toolbar-item-menu" style="display: none;">';

		foreach($this->cacheActions as $actionKey => $cacheAction) {
			$cacheMenu[] = '<li><a href="'.htmlspecialchars($cacheAction['href']).'">'.$cacheAction['icon'].' '.$cacheAction['title'].'</a></li>';
		}

		$cacheMenu[] = '</ul>';

		return implode("\n", $cacheMenu);
	}

	/**
	 * adds the neccessary javascript to the backend
	 *
	 * @return	void
	 */
	private function addJavascriptToBackend() {
		$this->backendReference->addJavascriptFile('js/clearcachemenu.js');
	}

	/**
	 * returns additional attributes for the list item in the toolbar
	 *
	 * @return	string		list item HTML attibutes
	 */
	public function getAdditionalAttributes() {
		return ' id="clear-cache-actions-menu"';
	}

}

if(defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.clearcachemenu.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.clearcachemenu.php']);
}

?>
