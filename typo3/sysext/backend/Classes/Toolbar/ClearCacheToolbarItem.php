<?php
namespace TYPO3\CMS\Backend\Toolbar;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Ingo Renner <ingo@typo3.org>
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
 * @author Ingo Renner <ingo@typo3.org>
 */
class ClearCacheToolbarItem implements \TYPO3\CMS\Backend\Toolbar\ToolbarItemHookInterface {

	/**
	 * @var array
	 */
	protected $cacheActions;

	/**
	 * @var array
	 */
	protected $optionValues;

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
		$this->cacheActions = array();
		$this->optionValues = array('all', 'pages');
		// Clear cache for ALL tables!
		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.all')) {
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.clearCacheMenu_all', TRUE);
			$this->cacheActions[] = array(
				'id' => 'all',
				'title' => $title,
				'href' => $this->backPath . 'tce_db.php?vC=' . $GLOBALS['BE_USER']->veriCode() . '&cacheCmd=all&ajaxCall=1' . \TYPO3\CMS\Backend\Utility\BackendUtility::getUrlToken('tceAction'),
				'icon' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-cache-clear-impact-high')
			);
		}
		// Clear cache for either ALL pages
		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.pages')) {
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.clearCacheMenu_pages', TRUE);
			$this->cacheActions[] = array(
				'id' => 'pages',
				'title' => $title,
				'href' => $this->backPath . 'tce_db.php?vC=' . $GLOBALS['BE_USER']->veriCode() . '&cacheCmd=pages&ajaxCall=1' . \TYPO3\CMS\Backend\Utility\BackendUtility::getUrlToken('tceAction'),
				'icon' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-cache-clear-impact-medium')
			);
		}
		// Clearing of cache-files in typo3conf/ + menu
		if ($GLOBALS['BE_USER']->isAdmin()) {
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.clearCacheMenu_allTypo3Conf', TRUE);
			$this->cacheActions[] = array(
				'id' => 'temp_CACHED',
				'title' => $title,
				'href' => $this->backPath . 'tce_db.php?vC=' . $GLOBALS['BE_USER']->veriCode() . '&cacheCmd=temp_CACHED&ajaxCall=1' . \TYPO3\CMS\Backend\Utility\BackendUtility::getUrlToken('tceAction'),
				'icon' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-cache-clear-impact-low')
			);
		}
		// Hook for manipulate cacheActions
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'] as $cacheAction) {
				$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($cacheAction);
				if (!$hookObject instanceof \TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface) {
					throw new \UnexpectedValueException('$hookObject must implement interface TYPO3\\CMS\\Backend\\Toolbar\\ClearCacheActionsHookInterface', 1228262000);
				}
				$hookObject->manipulateCacheActions($this->cacheActions, $this->optionValues);
			}
		}
	}

	/**
	 * Checks whether the user has access to this toolbar item
	 *
	 * @return boolean TRUE if user has access, FALSE if not
	 */
	public function checkAccess() {
		if ($GLOBALS['BE_USER']->isAdmin()) {
			return TRUE;
		}
		if (is_array($this->optionValues)) {
			foreach ($this->optionValues as $value) {
				if ($GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.' . $value)) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Creates the selector for workspaces
	 *
	 * @return string Workspace selector as HTML select
	 */
	public function render() {
		$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.clearCache_clearCache', TRUE);
		$this->addJavascriptToBackend();
		$cacheMenu = array();
		$cacheMenu[] = '<a href="#" class="toolbar-item">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-toolbar-menu-cache', array('title' => $title)) . '</a>';
		$cacheMenu[] = '<ul class="toolbar-item-menu" style="display: none;">';
		foreach ($this->cacheActions as $actionKey => $cacheAction) {
			$cacheMenu[] = '<li><a href="' . htmlspecialchars($cacheAction['href']) . '">' . $cacheAction['icon'] . ' ' . $cacheAction['title'] . '</a></li>';
		}
		$cacheMenu[] = '</ul>';
		return implode(LF, $cacheMenu);
	}

	/**
	 * Adds the necessary JavaScript to the backend
	 *
	 * @return void
	 */
	protected function addJavascriptToBackend() {
		$this->backendReference->addJavascriptFile('js/clearcachemenu.js');
	}

	/**
	 * Returns additional attributes for the list item in the toolbar
	 *
	 * @return string List item HTML attributes
	 */
	public function getAdditionalAttributes() {
		return ' id="clear-cache-actions-menu"';
	}

}


?>