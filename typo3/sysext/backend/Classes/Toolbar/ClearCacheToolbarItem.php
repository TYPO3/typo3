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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * class to render the menu for the cache clearing actions
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class ClearCacheToolbarItem implements ToolbarItemHookInterface {

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
	 * @var \TYPO3\CMS\Backend\Controller\BackendController
	 */
	protected $backendReference;

	/**
	 * TODO potentially unused
	 * @var string
	 */
	public $backPath = '';

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Backend\Controller\BackendController $backendReference TYPO3 backend object reference
	 * @throws \UnexpectedValueException
	 */
	public function __construct(\TYPO3\CMS\Backend\Controller\BackendController &$backendReference = NULL) {
		$this->backendReference = $backendReference;
		$this->cacheActions = array();
		$this->optionValues = array();
		$backendUser = $this->getBackendUser();

		// Clear all page-related caches
		if ($backendUser->isAdmin() || $backendUser->getTSConfigVal('options.clearCache.pages')) {
			$this->cacheActions[] = array(
				'id' => 'pages',
				'title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:flushPageCachesTitle', TRUE),
				'description' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:flushPageCachesDescription', TRUE),
				'href' => $this->backPath . 'tce_db.php?vC=' . $backendUser->veriCode() . '&cacheCmd=pages&ajaxCall=1' . BackendUtility::getUrlToken('tceAction'),
				'icon' => IconUtility::getSpriteIcon('actions-system-cache-clear-impact-low')
			);
			$this->optionValues[] = 'pages';
		}

		// Clear cache for ALL tables!
		if ($backendUser->isAdmin() || $backendUser->getTSConfigVal('options.clearCache.all')) {
			$this->cacheActions[] = array(
				'id' => 'all',
				'title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:flushGeneralCachesTitle', TRUE),
				'description' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:flushGeneralCachesDescription', TRUE),
				'href' => $this->backPath . 'tce_db.php?vC=' . $backendUser->veriCode() . '&cacheCmd=all&ajaxCall=1' . BackendUtility::getUrlToken('tceAction'),
				'icon' => IconUtility::getSpriteIcon('actions-system-cache-clear-impact-medium')
			);
			$this->optionValues[] = 'all';
		}

		// Clearing of system cache (core cache, class cache etc)
		// is only shown explicitly if activated for a BE-user (not activated for admins by default)
		// or if the system runs in development mode
		if ($backendUser->getTSConfigVal('options.clearCache.system') || GeneralUtility::getApplicationContext()->isDevelopment()) {
			$this->cacheActions[] = array(
				'id' => 'system',
				'title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:flushSystemCachesTitle', TRUE),
				'description' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:flushSystemCachesDescription', TRUE),
				'href' => $this->backPath . 'tce_db.php?vC=' . $backendUser->veriCode() . '&cacheCmd=system&ajaxCall=1' . BackendUtility::getUrlToken('tceAction'),
				'icon' => IconUtility::getSpriteIcon('actions-system-cache-clear-impact-high')
			);
			$this->optionValues[] = 'system';
		}
		// Hook for manipulating cacheActions
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'] as $cacheAction) {
				$hookObject = GeneralUtility::getUserObj($cacheAction);
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
		$backendUser = $this->getBackendUser();
		if ($backendUser->isAdmin()) {
			return TRUE;
		}
		if (is_array($this->optionValues)) {
			foreach ($this->optionValues as $value) {
				if ($backendUser->getTSConfigVal('options.clearCache.' . $value)) {
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
		$cacheMenu[] = '<a href="#" class="toolbar-item">' . IconUtility::getSpriteIcon('apps-toolbar-menu-cache', array('title' => $title)) . '</a>';
		$cacheMenu[] = '<ul class="toolbar-item-menu" style="display: none;">';
		foreach ($this->cacheActions as $actionKey => $cacheAction) {
			$cacheMenu[] = '<li><a href="' . htmlspecialchars($cacheAction['href'])
				. '" title="' . htmlspecialchars($cacheAction['description'] ?: $cacheAction['title']) . '">'
				. $cacheAction['icon'] . ' ' . htmlspecialchars($cacheAction['title']) . '</a></li>';
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
		$this->backendReference->addJavascriptFile('sysext/backend/Resources/Public/JavaScript/clearcachemenu.js');
	}

	/**
	 * Returns additional attributes for the list item in the toolbar
	 *
	 * @return string List item HTML attributes
	 */
	public function getAdditionalAttributes() {
		return 'id="clear-cache-actions-menu"';
	}

	/**
	 * Returns the current BE user.
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}
}
