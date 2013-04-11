<?php
namespace TYPO3\CMS\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Class for TYPO3 backend user authentication in the TSFE frontend
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * TYPO3 backend user authentication in the TSFE frontend.
 * This includes mainly functions related to the Admin Panel
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class FrontendBackendUserAuthentication extends \TYPO3\CMS\Core\Authentication\BackendUserAuthentication {

	/**
	 * Form field with login name.
	 *
	 * @var 	string
	 */
	public $formfield_uname = '';

	/**
	 * Form field with password.
	 *
	 * @var 	string
	 */
	public $formfield_uident = '';

	/**
	 * Form field with a unique value which is used to encrypt the password and username.
	 *
	 * @var 	string
	 */
	public $formfield_chalvalue = '';

	/**
	 * Sets the level of security. *'normal' = clear-text. 'challenged' = hashed password/username.
	 * from form in $formfield_uident. 'superchallenged' = hashed password hashed again with username.
	 *
	 * @var 	string
	 * @deprecated since 4.7 will be removed in 6.1
	 */
	public $security_level = '';

	/**
	 * Decides if the writelog() function is called at login and logout.
	 *
	 * @var 	boolean
	 */
	public $writeStdLog = FALSE;

	/**
	 * If the writelog() functions is called if a login-attempt has be tried without success.
	 *
	 * @var 	boolean
	 */
	public $writeAttemptLog = FALSE;

	/**
	 * Array of page related information (uid, title, depth).
	 *
	 * @var 	array
	 */
	public $extPageInTreeInfo = array();

	/**
	 * General flag which is set if the adminpanel should be displayed at all.
	 *
	 * @var 	boolean
	 */
	public $extAdmEnabled = FALSE;

	/**
	 * Instance of the admin panel
	 *
	 * @var 	tslib_AdminPanel
	 */
	public $adminPanel = NULL;

	/**
	 * @var \TYPO3\CMS\Core\FrontendEditing\FrontendEditingController
	 */
	public $frontendEdit = NULL;

	/**
	 * Initializes the admin panel.
	 *
	 * @return 	void
	 */
	public function initializeAdminPanel() {
		$this->extAdminConfig = $this->getTSConfigProp('admPanel');
		if (isset($this->extAdminConfig['enable.'])) {
			foreach ($this->extAdminConfig['enable.'] as $key => $value) {
				if ($value) {
					$this->adminPanel = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\View\\AdminPanelView');
					$this->extAdmEnabled = TRUE;
					break;
				}
			}
		}
	}

	/**
	 * Initializes frontend editing.
	 *
	 * @return void
	 */
	public function initializeFrontendEdit() {
		if (isset($this->extAdminConfig['enable.']) && $this->isFrontendEditingActive()) {
			foreach ($this->extAdminConfig['enable.'] as $key => $value) {
				if ($value) {
					if ($GLOBALS['TSFE'] instanceof \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController) {
						// Grab the Page TSConfig property that determines which controller to use.
						$pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
						$controllerKey = isset($pageTSConfig['TSFE.']['frontendEditingController']) ? $pageTSConfig['TSFE.']['frontendEditingController'] : 'default';
					} else {
						$controllerKey = 'default';
					}
					$controllerClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['frontendEditingController'][$controllerKey];
					if ($controllerClass) {
						$this->frontendEdit = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($controllerClass, FALSE);
					}
					break;
				}
			}
		}
	}

	/**
	 * Determines whether frontend editing is currently active.
	 *
	 * @return boolean Wheter frontend editing is active
	 */
	public function isFrontendEditingActive() {
		return $this->extAdmEnabled && ($this->adminPanel->isAdminModuleEnabled('edit') && $this->adminPanel->isAdminModuleOpen('edit') || $GLOBALS['TSFE']->displayEditIcons == 1);
	}

	/**
	 * Delegates to the appropriate view and renders the admin panel content.
	 *
	 * @return string.
	 */
	public function displayAdminPanel() {
		$content = $this->adminPanel->display();
		return $content;
	}

	/**
	 * Determines whether the admin panel is enabled and visible.
	 *
	 * @return boolean Whether the admin panel is enabled and visible
	 */
	public function isAdminPanelVisible() {
		return $this->extAdmEnabled && !$this->extAdminConfig['hide'] && $GLOBALS['TSFE']->config['config']['admPanel'];
	}

	/*****************************************************
	 *
	 * TSFE BE user Access Functions
	 *
	 ****************************************************/
	/**
	 * Implementing the access checks that the typo3/init.php script does before a user is ever logged in.
	 * Used in the frontend.
	 *
	 * @return boolean Returns TRUE if access is OK
	 */
	public function checkBackendAccessSettingsFromInitPhp() {
		// Check Hardcoded lock on BE
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'] < 0) {
			return FALSE;
		}
		// Check IP
		if (trim($GLOBALS['TYPO3_CONF_VARS']['BE']['IPmaskList'])) {
			if (!\TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['BE']['IPmaskList'])) {
				return FALSE;
			}
		}
		// Check SSL (https)
		if (intval($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL']) && $GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] != 3) {
			if (!\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SSL')) {
				return FALSE;
			}
		}
		// Finally a check from \TYPO3\CMS\Core\Authentication\BackendUserAuthentication::backendCheckLogin()
		if ($this->isUserAllowedToLogin()) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Evaluates if the Backend User has read access to the input page record.
	 * The evaluation is based on both read-permission and whether the page is found in one of the users webmounts. Only if both conditions are TRUE will the function return TRUE.
	 * Read access means that previewing is allowed etc.
	 * Used in index_ts.php
	 *
	 * @param array $pageRec The page record to evaluate for
	 * @return boolean TRUE if read access
	 */
	public function extPageReadAccess($pageRec) {
		return $this->isInWebMount($pageRec['uid']) && $this->doesUserHaveAccess($pageRec, 1);
	}

	/*****************************************************
	 *
	 * TSFE BE user Access Functions
	 *
	 ****************************************************/
	/**
	 * Generates a list of Page-uid's from $id. List does not include $id itself
	 * The only pages excluded from the list are deleted pages.
	 *
	 * @param integer $id Start page id
	 * @param integer $depth Depth to traverse down the page tree.
	 * @param integer $begin Is an optional integer that determines at which level in the tree to start collecting uid's. Zero means 'start right away', 1 = 'next level and out'
	 * @param string $perms_clause Perms clause
	 * @return string Returns the list with a comma in the end (if any pages selected!)
	 */
	public function extGetTreeList($id, $depth, $begin = 0, $perms_clause) {
		$depth = intval($depth);
		$begin = intval($begin);
		$id = intval($id);
		$theList = '';
		if ($id && $depth > 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,title', 'pages', 'pid=' . $id . ' AND doktype IN (' . $GLOBALS['TYPO3_CONF_VARS']['FE']['content_doktypes'] . ') AND deleted=0 AND ' . $perms_clause);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if ($begin <= 0) {
					$theList .= $row['uid'] . ',';
					$this->extPageInTreeInfo[] = array($row['uid'], htmlspecialchars($row['title'], $depth));
				}
				if ($depth > 1) {
					$theList .= $this->extGetTreeList($row['uid'], $depth - 1, $begin - 1, $perms_clause);
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $theList;
	}

	/**
	 * Returns the number of cached pages for a page id.
	 *
	 * @param integer $pageId The page id.
	 * @return integer The number of pages for this page in the table "cache_pages
	 */
	public function extGetNumberOfCachedPages($pageId) {
		$pageCache = $GLOBALS['typo3CacheManager']->getCache('cache_pages');
		$pageCacheEntries = $pageCache->getByTag('pageId_' . (int) $pageId);
		return count($pageCacheEntries);
	}

	/*****************************************************
	 *
	 * Localization handling
	 *
	 ****************************************************/
	/**
	 * Returns the label for key, $key. If a translation for the language set in $this->uc['lang'] is found that is returned, otherwise the default value.
	 * IF the global variable $LOCAL_LANG is NOT an array (yet) then this function loads the global $LOCAL_LANG array with the content of "sysext/lang/locallang_tsfe.xlf" so that the values therein can be used for labels in the Admin Panel
	 *
	 * @param string $key Key for a label in the $GLOBALS['LOCAL_LANG'] array of "sysext/lang/locallang_tsfe.xlf
	 * @return string The value for the $key
	 */
	public function extGetLL($key) {
		if (!is_array($GLOBALS['LOCAL_LANG'])) {
			$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_tsfe.xlf');
			if (!is_array($GLOBALS['LOCAL_LANG'])) {
				$GLOBALS['LOCAL_LANG'] = array();
			}
		}
		// Label string in the default backend output charset.
		$labelStr = htmlspecialchars($GLOBALS['LANG']->getLL($key));
		$labelStr = $GLOBALS['LANG']->csConvObj->utf8_to_entities($labelStr);
		// Return the result:
		return $labelStr;
	}

}


?>