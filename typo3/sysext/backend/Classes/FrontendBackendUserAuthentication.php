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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 backend user authentication in the TSFE frontend.
 * This includes mainly functions related to the Admin Panel
 */
class FrontendBackendUserAuthentication extends BackendUserAuthentication
{
    use PublicPropertyDeprecationTrait;

    /**
     * Properties which have been moved to protected status from public
     *
     * @var array
     */
    protected $deprecatedPublicProperties = [
        'extAdmEnabled' => 'Using $extAdmEnabled of class FrontendBackendUserAuthentication from the outside is discouraged, as this variable is only used for internal storage.',
        'adminPanel' => 'Using $adminPanel of class FrontendBackendUserAuthentication from the outside is discouraged, as this variable is only used for internal storage.',
        'extAdminConfig' => 'Using $extAdminConfig of class FrontendBackendUserAuthentication from the outside is discouraged, as this variable is only used for internal storage.',
    ];

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
     * Formfield_status should be set to "". The value this->formfield_status is set to empty in order to
     * disable login-attempts to the backend account through this script
     *
     * @var string
     */
    public $formfield_status = '';

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
     * General flag which is set if the adminpanel is enabled at all.
     *
     * @var bool
     * @deprecated since TYPO3v9, property will be removed in TYPO3 v10 - see extension "adminpanel" for new API
     */
    public $extAdmEnabled = false;

    /**
     * @var \TYPO3\CMS\Adminpanel\View\AdminPanelView Instance of admin panel
     * @deprecated since TYPO3v9, property will be removed in TYPO3 v10 - see extension "adminpanel" for new API
     */
    public $adminPanel = null;

    /**
     * @var \TYPO3\CMS\Core\FrontendEditing\FrontendEditingController
     */
    public $frontendEdit = null;

    /**
     * @var array
     * @deprecated since TYPO3v9, property will be removed in TYPO3 v10 - see extension "adminpanel" for new API
     */
    public $extAdminConfig = [];

    /**
     * Initializes the admin panel.
     *
     * @deprecated since TYPO3v9 - rewritten as middleware
     */
    public function initializeAdminPanel()
    {
        trigger_error('Method will be removed in TYPO3 v10 - initialization is done via middleware.', E_USER_DEPRECATED);
    }

    /**
     * Initializes frontend editing.
     *
     * @deprecated since TYPO3v9 - rewritten as middleware
     */
    public function initializeFrontendEdit()
    {
        trigger_error('Method will be removed in TYPO3 v10 - initialization is done via middleware.', E_USER_DEPRECATED);
    }

    /**
     * Determines whether frontend editing is currently active.
     *
     * @deprecated since TYPO3 v9 - see ext "feedit" for API
     * @return bool Whether frontend editing is active
     */
    public function isFrontendEditingActive()
    {
        trigger_error('Method will be removed in TYPO3 v10 - use underlying TSFE directly.', E_USER_DEPRECATED);
        return $this->extAdmEnabled && (
            $this->adminPanel->isAdminModuleEnabled('edit') ||
            (int)$GLOBALS['TSFE']->displayEditIcons === 1 ||
            (int)$GLOBALS['TSFE']->displayFieldEditIcons === 1
        );
    }

    /**
     * Delegates to the appropriate view and renders the admin panel content.
     *
     * @deprecated since TYPO3v9 - see ext "adminpanel" for new API
     * @return string.
     */
    public function displayAdminPanel()
    {
        trigger_error('Method will be removed in TYPO3 v10 - use MainController of adminpanel extension.', E_USER_DEPRECATED);
        return $this->adminPanel->display();
    }

    /**
     * Determines whether the admin panel is enabled and visible.
     *
     * @deprecated since TYPO3v9 - see ext "adminpanel" for new API
     * @return bool true if the admin panel is enabled and visible
     */
    public function isAdminPanelVisible()
    {
        trigger_error('Method will be removed in TYPO3 v10 - use new adminpanel API instead.', E_USER_DEPRECATED);
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
            if (!GeneralUtility::cmpIP(GeneralUtility::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['BE']['IPmaskList'])) {
                return false;
            }
        }
        // Check IP mask based on TSconfig
        if (!$this->checkLockToIP()) {
            return false;
        }
        // Check SSL (https)
        if ((bool)$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] && !GeneralUtility::getIndpEnv('TYPO3_SSL')) {
            return false;
        }
        // Finally a check as in BackendUserAuthentication::backendCheckLogin()
        return $this->isUserAllowedToLogin();
    }

    /**
     * Evaluates if the Backend User has read access to the input page record.
     * The evaluation is based on both read-permission and whether the page is found in one of the users webmounts.
     * Only if both conditions match, will the function return TRUE.
     *
     * Read access means that previewing is allowed etc.
     *
     * Used in \TYPO3\CMS\Frontend\Http\RequestHandler
     *
     * @param array $pageRec The page record to evaluate for
     * @return bool TRUE if read access
     */
    public function extPageReadAccess($pageRec)
    {
        return $this->isInWebMount($pageRec['uid']) && $this->doesUserHaveAccess($pageRec, Permission::PAGE_SHOW);
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
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $depth = (int)$depth;
        $begin = (int)$begin;
        $id = (int)$id;
        $theList = '';
        if ($id && $depth > 0) {
            $result = $queryBuilder
                ->select('uid', 'title')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)),
                    QueryHelper::stripLogicalOperatorPrefix($perms_clause)
                )
                ->execute();
            while ($row = $result->fetch()) {
                if ($begin <= 0) {
                    $theList .= $row['uid'] . ',';
                }
                if ($depth > 1) {
                    $theList .= $this->extGetTreeList($row['uid'], $depth - 1, $begin - 1, $perms_clause);
                }
            }
        }
        return $theList;
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
     * the global $LOCAL_LANG array with the content of "EXT:lang/Resources/Private/Language/locallang_tsfe.xlf"
     * such that the values therein can be used for labels in the Admin Panel
     *
     * @param string $key Key for a label in the $GLOBALS['LOCAL_LANG'] array of "EXT:lang/Resources/Private/Language/locallang_tsfe.xlf
     * @return string The value for the $key
     */
    public function extGetLL($key)
    {
        if (!is_array($GLOBALS['LOCAL_LANG'])) {
            $this->getLanguageService()->includeLLFile('EXT:lang/Resources/Private/Language/locallang_tsfe.xlf');
            if (!is_array($GLOBALS['LOCAL_LANG'])) {
                $GLOBALS['LOCAL_LANG'] = [];
            }
        }
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
