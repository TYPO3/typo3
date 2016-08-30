<?php
namespace TYPO3\CMS\Backend;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * TYPO3 backend user authentication in the TSFE frontend.
 * This includes mainly functions related to the Admin Panel
 */
class FrontendBackendUserAuthentication extends \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
{
    /**
     * Form field with login name.
     *
     * @var string
     */
    public $formfield_uname = '';

    /**
     * Form field with password.
     *
     * @var string
     */
    public $formfield_uident = '';

    /**
     * Decides if the writelog() function is called at login and logout.
     *
     * @var bool
     */
    public $writeStdLog = false;

    /**
     * If the writelog() functions is called if a login-attempt has be tried without success.
     *
     * @var bool
     */
    public $writeAttemptLog = false;

    /**
     * Array of page related information (uid, title, depth).
     *
     * @var array
     */
    public $extPageInTreeInfo = [];

    /**
     * General flag which is set if the adminpanel is enabled at all.
     *
     * @var bool
     */
    public $extAdmEnabled = false;

    /**
     * @var \TYPO3\CMS\Frontend\View\AdminPanelView Instance of admin panel
     */
    public $adminPanel = null;

    /**
     * @var \TYPO3\CMS\Core\FrontendEditing\FrontendEditingController
     */
    public $frontendEdit = null;

    /**
     * @var array
     */
    public $extAdminConfig = [];

    /**
     * Initializes the admin panel.
     *
     * @return void
     */
    public function initializeAdminPanel()
    {
        $this->extAdminConfig = $this->getTSConfigProp('admPanel');
        if (isset($this->extAdminConfig['enable.'])) {
            foreach ($this->extAdminConfig['enable.'] as $value) {
                if ($value) {
                    $this->adminPanel = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\View\AdminPanelView::class);
                    $this->extAdmEnabled = true;
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
    public function initializeFrontendEdit()
    {
        if (isset($this->extAdminConfig['enable.']) && $this->isFrontendEditingActive()) {
            foreach ($this->extAdminConfig['enable.'] as $value) {
                if ($value) {
                    if ($GLOBALS['TSFE'] instanceof \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController) {
                        // Grab the Page TSConfig property that determines which controller to use.
                        $pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
                        $controllerKey = isset($pageTSConfig['TSFE.']['frontendEditingController'])
                            ? $pageTSConfig['TSFE.']['frontendEditingController']
                            : 'default';
                    } else {
                        $controllerKey = 'default';
                    }
                    $controllerClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['frontendEditingController'][$controllerKey];
                    if ($controllerClass) {
                        $this->frontendEdit = GeneralUtility::getUserObj($controllerClass);
                    }
                    break;
                }
            }
        }
    }

    /**
     * Determines whether frontend editing is currently active.
     *
     * @return bool Whether frontend editing is active
     */
    public function isFrontendEditingActive()
    {
        return $this->extAdmEnabled && (
            $this->adminPanel->isAdminModuleEnabled('edit') ||
            $GLOBALS['TSFE']->displayEditIcons == 1 ||
            $GLOBALS['TSFE']->displayFieldEditIcons == 1
        );
    }

    /**
     * Delegates to the appropriate view and renders the admin panel content.
     *
     * @return string.
     */
    public function displayAdminPanel()
    {
        return $this->adminPanel->display();
    }

    /**
     * Determines whether the admin panel is enabled and visible.
     *
     * @return bool Whether the admin panel is enabled and visible
     */
    public function isAdminPanelVisible()
    {
        return $this->extAdmEnabled && !$this->extAdminConfig['hide'] && $GLOBALS['TSFE']->config['config']['admPanel'];
    }

    /*****************************************************
     *
     * TSFE BE user Access Functions
     *
     ****************************************************/
    /**
     * Implementing the access checks that the TYPO3 CMS bootstrap script does before a user is ever logged in.
     * Used in the frontend.
     *
     * @return bool Returns TRUE if access is OK
     */
    public function checkBackendAccessSettingsFromInitPhp()
    {
        // Check Hardcoded lock on BE
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'] < 0) {
            return false;
        }
        // Check IP
        if (trim($GLOBALS['TYPO3_CONF_VARS']['BE']['IPmaskList'])) {
            $remoteAddress = GeneralUtility::getIndpEnv('REMOTE_ADDR');
            if (!GeneralUtility::cmpIP($remoteAddress, $GLOBALS['TYPO3_CONF_VARS']['BE']['IPmaskList'])) {
                return false;
            }
        }
        // Check SSL (https)
        if ((int)$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] && !GeneralUtility::getIndpEnv('TYPO3_SSL')) {
            return false;
        }
        // Finally a check from \TYPO3\CMS\Core\Authentication\BackendUserAuthentication::backendCheckLogin()
        if ($this->isUserAllowedToLogin()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Evaluates if the Backend User has read access to the input page record.
     * The evaluation is based on both read-permission and whether the page is found in one of the users webmounts.
     * Only if both conditions are TRUE will the function return TRUE.
     * Read access means that previewing is allowed etc.
     * Used in \TYPO3\CMS\Frontend\Http\RequestHandler
     *
     * @param array $pageRec The page record to evaluate for
     * @return bool TRUE if read access
     */
    public function extPageReadAccess($pageRec)
    {
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
     * @param int $id Start page id
     * @param int $depth Depth to traverse down the page tree.
     * @param int $begin Is an optional integer that determines at which level in the tree to start collecting uid's. Zero means 'start right away', 1 = 'next level and out'
     * @param string $perms_clause Perms clause
     * @return string Returns the list with a comma in the end (if any pages selected!)
     */
    public function extGetTreeList($id, $depth, $begin = 0, $perms_clause)
    {
        $depth = (int)$depth;
        $begin = (int)$begin;
        $id = (int)$id;
        $theList = '';
        if ($id && $depth > 0) {
            $where = 'pid=' . $id . ' AND doktype IN (' . $GLOBALS['TYPO3_CONF_VARS']['FE']['content_doktypes']
                . ') AND deleted=0 AND ' . $perms_clause;
            $res = $this->db->exec_SELECTquery('uid,title', 'pages', $where);
            while (($row = $this->db->sql_fetch_assoc($res))) {
                if ($begin <= 0) {
                    $theList .= $row['uid'] . ',';
                    $this->extPageInTreeInfo[] = [$row['uid'], htmlspecialchars($row['title'], $depth)];
                }
                if ($depth > 1) {
                    $theList .= $this->extGetTreeList($row['uid'], $depth - 1, $begin - 1, $perms_clause);
                }
            }
            $this->db->sql_free_result($res);
        }
        return $theList;
    }

    /**
     * Returns the number of cached pages for a page id.
     *
     * @param int $pageId The page id.
     * @return int The number of pages for this page in the table "cache_pages
     */
    public function extGetNumberOfCachedPages($pageId)
    {
        /** @var FrontendInterface $pageCache */
        $pageCache = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('cache_pages');
        $pageCacheEntries = $pageCache->getByTag('pageId_' . (int)$pageId);
        return count($pageCacheEntries);
    }

    /*****************************************************
     *
     * Localization handling
     *
     ****************************************************/
    /**
     * Returns the label for key. If a translation for the language set in $this->uc['lang']
     * is found that is returned, otherwise the default value.
     * If the global variable $LOCAL_LANG is NOT an array (yet) then this function loads
     * the global $LOCAL_LANG array with the content of "sysext/lang/locallang_tsfe.xlf"
     * such that the values therein can be used for labels in the Admin Panel
     *
     * @param string $key Key for a label in the $GLOBALS['LOCAL_LANG'] array of "sysext/lang/locallang_tsfe.xlf
     * @return string The value for the $key
     */
    public function extGetLL($key)
    {
        if (!is_array($GLOBALS['LOCAL_LANG'])) {
            $this->getLanguageService()->includeLLFile('EXT:lang/locallang_tsfe.xlf');
            if (!is_array($GLOBALS['LOCAL_LANG'])) {
                $GLOBALS['LOCAL_LANG'] = [];
            }
        }
        // Return the label string in the default backend output charset.
        return htmlspecialchars($this->getLanguageService()->getLL($key));
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
