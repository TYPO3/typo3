<?php
namespace TYPO3\CMS\Backend\Toolbar;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Michael Klapper <michael.klapper@aoemedia.de>
 *  (c) 2010-2013 Jeff Segars <jeff@webempoweredchurch.org>
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
 * Adds backend live search to the toolbar
 *
 * @author Michael Klapper <michael.klapper@aoemedia.de>
 * @author Jeff Segars <jeff@webempoweredchurch.org>
 */
class LiveSearchToolbarItem implements \TYPO3\CMS\Backend\Toolbar\ToolbarItemHookInterface {

	/**
	 * Reference back to the backend object
	 *
	 * @var 	TYPO3backend
	 */
	protected $backendReference;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Backend\Controller\BackendController $backendReference TYPO3 backend object reference
	 */
	public function __construct(\TYPO3\CMS\Backend\Controller\BackendController &$backendReference = NULL) {
		$this->backendReference = $backendReference;
	}

	/**
	 * Checks whether the user has access to this toolbar item
	 *
	 * @return boolean TRUE if user has access, FALSE if not
	 */
	public function checkAccess() {
		$access = FALSE;
		// Loads the backend modules available for the logged in user.
		$loadModules = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Module\\ModuleLoader');
		$loadModules->observeWorkspaces = TRUE;
		$loadModules->load($GLOBALS['TBE_MODULES']);
		// Live search is heavily dependent on the list module and only available when that module is.
		if (is_array($loadModules->modules['web']['sub']['list'])) {
			$access = TRUE;
		}
		return $access;
	}

	/**
	 * Creates the selector for workspaces
	 *
	 * @return string Workspace selector as HTML select
	 */
	public function render() {
		$this->addJavascriptToBackend();
		return '<div class="live-search-wrapper">
					<span title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:search') . '" class="t3-icon t3-icon-apps t3-icon-apps-toolbar t3-icon-toolbar-menu-search">&nbsp;</span>
					<input id="live-search-box" />
				</div>';
	}

	/**
	 * Adds the necessary JavaScript to the backend
	 *
	 * @return void
	 */
	protected function addJavascriptToBackend() {
		$pageRenderer = $GLOBALS['TBE_TEMPLATE']->getPageRenderer();
		$this->backendReference->addJavascriptFile('js/livesearch.js');
	}

	/**
	 * returns additional attributes for the list item in the toolbar
	 *
	 * @return string List item HTML attributes
	 */
	public function getAdditionalAttributes() {
		return ' id="live-search-menu"';
	}

}


?>