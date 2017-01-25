<?php
namespace TYPO3\CMS\Version\Hook;

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

use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Hook for checking if the preview mode is activated
 * preview mode = show a page of a workspace without having to log in
 */
class PreviewHook implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * the GET parameter to be used
     *
     * @var string
     */
    protected $previewKey = 'ADMCMD_prev';

    /**
     * instance of the \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController object
     *
     * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected $tsfeObj;

    /**
     * preview configuration
     *
     * @var array
     */
    protected $previewConfiguration = false;

    /**
     * Defines whether to force read permissions on pages.
     *
     * @var bool
     * @see \TYPO3\CMS\Core\Authentication\BackendUserAuthentication::getPagePermsClause
     */
    protected $forceReadPermissions = false;

    /**
     * hook to check if the preview is activated
     * right now, this hook is called at the end of "$TSFE->connectToDB"
     *
     * @param array $params (not needed right now)
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $pObj
     * @return void
     */
    public function checkForPreview($params, &$pObj)
    {
        $this->tsfeObj = $pObj;
        $this->previewConfiguration = $this->getPreviewConfiguration();
        if (is_array($this->previewConfiguration)) {
            // In case of a keyword-authenticated preview,
            // re-initialize the TSFE object:
            // because the GET variables are taken from the preview
            // configuration
            $this->tsfeObj = GeneralUtility::makeInstance(
                \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class,
                $GLOBALS['TYPO3_CONF_VARS'],
                GeneralUtility::_GP('id'),
                GeneralUtility::_GP('type'),
                GeneralUtility::_GP('no_cache'),
                GeneralUtility::_GP('cHash'),
                GeneralUtility::_GP('jumpurl'),
                GeneralUtility::_GP('MP'),
                GeneralUtility::_GP('RDCT')
            );
            $GLOBALS['TSFE'] = $this->tsfeObj;
            // Configuration after initialization of TSFE object.
            // Basically this unsets the BE cookie if any and forces
            // the BE user set according to the preview configuration.
            // @previouslyknownas TSFE->ADMCMD_preview_postInit
            // Clear cookies:
            unset($_COOKIE['be_typo_user']);
        }
    }

    /**
     * hook after the regular BE user has been initialized
     * if there is no BE user login, but a preview configuration
     * the BE user of the preview configuration gets initialized
     *
     * @param array $params holding the BE_USER object
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $pObj
     * @return void
     */
    public function initializePreviewUser(&$params, &$pObj)
    {
        // if there is a valid BE user, and the full workspace should be previewed, the workspacePreview option should be set
        $workspaceUid = $this->previewConfiguration['fullWorkspace'];
        $workspaceRecord = null;
        if ((is_null($params['BE_USER']) || $params['BE_USER'] === false) && $this->previewConfiguration !== false && $this->previewConfiguration['BEUSER_uid'] > 0) {
            // First initialize a temp user object and resolve usergroup information
            /** @var FrontendBackendUserAuthentication $tempBackendUser */
            $tempBackendUser = $this->createFrontendBackendUser();
            $tempBackendUser->userTS_dontGetCached = 1;
            $tempBackendUser->setBeUserByUid($this->previewConfiguration['BEUSER_uid']);
            if ($tempBackendUser->user['uid']) {
                $tempBackendUser->unpack_uc('');
                $tempBackendUser->fetchGroupData();
                // Handle degradation of admin users
                if ($tempBackendUser->isAdmin() && ExtensionManagementUtility::isLoaded('workspaces')) {
                    $workspaceRecord = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                        'uid, adminusers, reviewers, members, db_mountpoints',
                        'sys_workspace',
                        'pid=0 AND uid=' . (int)$workspaceUid . BackendUtility::deleteClause('sys_workspace')
                    );
                    // Either use configured workspace mount or current page id, if admin user does not have any page mounts
                    if (empty($tempBackendUser->groupData['webmounts'])) {
                        $tempBackendUser->groupData['webmounts'] = !empty($workspaceRecord['db_mountpoints']) ? $workspaceRecord['db_mountpoints'] : $pObj->id;
                    }
                    // Force add degraded admin user as member of this workspace
                    $workspaceRecord['members'] = 'be_users_' . $this->previewConfiguration['BEUSER_uid'];
                    // Force read permission for degraded admin user
                    $this->forceReadPermissions = true;
                }
                // Store only needed information in the real simulate backend
                $BE_USER = $this->createFrontendBackendUser();
                $BE_USER->userTS_dontGetCached = 1;
                $BE_USER->user = $tempBackendUser->user;
                $BE_USER->user['admin'] = 0;
                $BE_USER->groupData['webmounts'] = $tempBackendUser->groupData['webmounts'];
                $BE_USER->groupList = $tempBackendUser->groupList;
                $BE_USER->userGroups = $tempBackendUser->userGroups;
                $BE_USER->userGroupsUID = $tempBackendUser->userGroupsUID;
                $pObj->beUserLogin = true;
            } else {
                $BE_USER = null;
                $pObj->beUserLogin = false;
            }
            unset($tempBackendUser);
            $params['BE_USER'] = $BE_USER;
        }
        if ($pObj->beUserLogin
            && is_object($params['BE_USER'])
            && MathUtility::canBeInterpretedAsInteger($workspaceUid)
        ) {
            if ($workspaceUid == 0
                || $workspaceUid >= -1
                && $params['BE_USER']->checkWorkspace($workspaceRecord ?: $workspaceUid)
                && $params['BE_USER']->isInWebMount($pObj->id)
            ) {
                // Check Access to workspace. Live (0) is OK to preview for all.
                $pObj->workspacePreview = (int)$workspaceUid;
            } else {
                // No preview, will default to "Live" at the moment
                $pObj->workspacePreview = -99;
            }
        }
    }

    /**
     * Overrides the page permission clause in case an admin
     * user has been degraded to a regular user without any user
     * group assignments. This method is used as hook callback.
     *
     * @param array $parameters
     * @return string
     * @see \TYPO3\CMS\Core\Authentication\BackendUserAuthentication::getPagePermsClause
     */
    public function overridePagePermissionClause(array $parameters)
    {
        $clause = $parameters['currentClause'];
        if ($parameters['perms'] & 1 && $this->forceReadPermissions) {
            $clause = ' 1=1';
        }
        return $clause;
    }

    /**
     * Overrides the row permission value in case an admin
     * user has been degraded to a regular user without any user
     * group assignments. This method is used as hook callback.
     *
     * @param array $parameters
     * @return int
     * @see \TYPO3\CMS\Core\Authentication\BackendUserAuthentication::calcPerms
     */
    public function overridePermissionCalculation(array $parameters)
    {
        $permissions = $parameters['outputPermissions'];
        if (!($permissions & Permission::PAGE_SHOW) && $this->forceReadPermissions) {
            $permissions |= Permission::PAGE_SHOW;
        }
        return $permissions;
    }

    /**
     * Looking for an ADMCMD_prev code, looks it up if found and returns configuration data.
     * Background: From the backend a request to the frontend to show a page, possibly with
     * workspace preview can be "recorded" and associated with a keyword.
     * When the frontend is requested with this keyword the associated request parameters are
     * restored from the database AND the backend user is loaded - only for that request.
     * The main point is that a special URL valid for a limited time,
     * eg. http://localhost/typo3site/index.php?ADMCMD_prev=035d9bf938bd23cb657735f68a8cedbf will
     * open up for a preview that doesn't require login. Thus it's useful for sending in an email
     * to someone without backend account.
     * This can also be used to generate previews of hidden pages, start/endtimes, usergroups and
     * those other settings from the Admin Panel - just not implemented yet.
     *
     * @throws \Exception
     * @return array Preview configuration array from sys_preview record.
     */
    public function getPreviewConfiguration()
    {
        $inputCode = $this->getPreviewInputCode();
        // If input code is available and shall not be ignored, look up the settings
        if ($inputCode && $inputCode !== 'IGNORE') {
            // "log out"
            if ($inputCode == 'LOGOUT') {
                setcookie($this->previewKey, '', 0, GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
                if ($this->tsfeObj->TYPO3_CONF_VARS['FE']['workspacePreviewLogoutTemplate']) {
                    $templateFile = PATH_site . $this->tsfeObj->TYPO3_CONF_VARS['FE']['workspacePreviewLogoutTemplate'];
                    if (@is_file($templateFile)) {
                        $message = GeneralUtility::getUrl(PATH_site . $this->tsfeObj->TYPO3_CONF_VARS['FE']['workspacePreviewLogoutTemplate']);
                    } else {
                        $message = '<strong>ERROR!</strong><br>Template File "'
                            . $this->tsfeObj->TYPO3_CONF_VARS['FE']['workspacePreviewLogoutTemplate']
                            . '" configured with $TYPO3_CONF_VARS["FE"]["workspacePreviewLogoutTemplate"] not found. Please contact webmaster about this problem.';
                    }
                } else {
                    $message = 'You logged out from Workspace preview mode. Click this link to <a href="%1$s">go back to the website</a>';
                }
                $returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GET('returnUrl'));
                die(sprintf($message, htmlspecialchars(preg_replace('/\\&?' . $this->previewKey . '=[[:alnum:]]+/', '', $returnUrl))));
            }
            // Look for keyword configuration record:
            $where = 'keyword=' . $this->getDatabaseConnection()->fullQuoteStr($inputCode, 'sys_preview') . ' AND endtime>' . $GLOBALS['EXEC_TIME'];
            $previewData = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'sys_preview', $where);
            // Get: Backend login status, Frontend login status
            // - Make sure to remove fe/be cookies (temporarily);
            // BE already done in ADMCMD_preview_postInit()
            if (is_array($previewData)) {
                if (empty(GeneralUtility::_POST())) {
                    // Unserialize configuration:
                    $previewConfig = unserialize($previewData['config']);
                    // For full workspace preview we only ADD a get variable
                    // to set the preview of the workspace - so all other Get
                    // vars are accepted. Hope this is not a security problem.
                    // Still posting is not allowed and even if a backend user
                    // get initialized it shouldn't lead to situations where
                    // users can use those credentials.
                    if ($previewConfig['fullWorkspace']) {
                        // Set the workspace preview value:
                        GeneralUtility::_GETset($previewConfig['fullWorkspace'], 'ADMCMD_previewWS');
                        // If ADMCMD_prev is set the $inputCode value cannot come
                        // from a cookie and we set that cookie here. Next time it will
                        // be found from the cookie if ADMCMD_prev is not set again...
                        if (GeneralUtility::_GP($this->previewKey)) {
                            // Lifetime is 1 hour, does it matter much?
                            // Requires the user to click the link from their email again if it expires.
                            setcookie($this->previewKey, GeneralUtility::_GP($this->previewKey), 0, GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
                        }
                        return $previewConfig;
                    } elseif (GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'index.php?' . $this->previewKey . '=' . $inputCode === GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')) {
                        // Set GET variables
                        $GET_VARS = '';
                        parse_str($previewConfig['getVars'], $GET_VARS);
                        GeneralUtility::_GETset($GET_VARS);
                        // Return preview keyword configuration
                        return $previewConfig;
                    } else {
                        // This check is to prevent people from setting additional
                        // GET vars via realurl or other URL path based ways of passing parameters.
                        throw new \Exception(htmlspecialchars('Request URL did not match "'
                            . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'index.php?' . $this->previewKey . '='
                            . $inputCode . '"', 1294585190));
                    }
                } else {
                    throw new \Exception('POST requests are incompatible with keyword preview.', 1294585191);
                }
            } else {
                throw new \Exception('ADMCMD command could not be executed! (No keyword configuration found)', 1294585192);
            }
        }
        return false;
    }

    /**
     * returns the input code value from the admin command variable
     *
     * @return string Input code
     */
    protected function getPreviewInputCode()
    {
        $inputCode = GeneralUtility::_GP($this->previewKey);
        // If no inputcode and a cookie is set, load input code from cookie:
        if (!$inputCode && $_COOKIE[$this->previewKey]) {
            $inputCode = $_COOKIE[$this->previewKey];
        }
        return $inputCode;
    }

    /**
     * Set preview keyword, eg:
     * $previewUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL').'index.php?ADMCMD_prev='.$this->compilePreviewKeyword('id='.$pageId.'&L='.$language.'&ADMCMD_view=1&ADMCMD_editIcons=1&ADMCMD_previewWS='.$this->workspace, $GLOBALS['BE_USER']->user['uid'], 120);
     *
     * @todo for sys_preview:
     * - Add a comment which can be shown to previewer in frontend in some way (plus maybe ability to write back, take other action?)
     * - Add possibility for the preview keyword to work in the backend as well: So it becomes a quick way to a certain action of sorts?
     *
     * @param string $getVarsStr Get variables to preview, eg. 'id=1150&L=0&ADMCMD_view=1&ADMCMD_editIcons=1&ADMCMD_previewWS=8'
     * @param string $backendUserUid 32 byte MD5 hash keyword for the URL: "?ADMCMD_prev=[keyword]
     * @param int $ttl Time-To-Live for keyword
     * @param int|NULL $fullWorkspace Which workspace to preview. Workspace UID, -1 or >0. If set, the getVars is ignored in the frontend, so that string can be empty
     * @return string Returns keyword to use in URL for ADMCMD_prev=
     */
    public function compilePreviewKeyword($getVarsStr, $backendUserUid, $ttl = 172800, $fullWorkspace = null)
    {
        $fieldData = [
            'keyword' => md5(uniqid(microtime(), true)),
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'endtime' => $GLOBALS['EXEC_TIME'] + $ttl,
            'config' => serialize([
                'fullWorkspace' => $fullWorkspace,
                'getVars' => $getVarsStr,
                'BEUSER_uid' => $backendUserUid
            ])
        ];
        $this->getDatabaseConnection()->exec_INSERTquery('sys_preview', $fieldData);
        return $fieldData['keyword'];
    }

    /**
     * easy function to just return the number of hours
     * a preview link is valid, based on the TSconfig value "options.workspaces.previewLinkTTLHours"
     * by default, it's 48hs
     *
     * @return int The hours as a number
     */
    public function getPreviewLinkLifetime()
    {
        $ttlHours = (int)$GLOBALS['BE_USER']->getTSConfigVal('options.workspaces.previewLinkTTLHours');
        return $ttlHours ? $ttlHours : 24 * 2;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return FrontendBackendUserAuthentication
     */
    protected function createFrontendBackendUser()
    {
        return GeneralUtility::makeInstance(
            FrontendBackendUserAuthentication::class
        );
    }
}
