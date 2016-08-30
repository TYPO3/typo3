<?php
namespace TYPO3\CMS\Beuser\Controller;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Backend module user administration controller
 */
class BackendUserController extends BackendUserActionController
{
    /**
     * @var \TYPO3\CMS\Beuser\Domain\Model\ModuleData
     */
    protected $moduleData;

    /**
     * @var \TYPO3\CMS\Beuser\Service\ModuleDataStorageService
     */
    protected $moduleDataStorageService;

    /**
     * @var \TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository
     */
    protected $backendUserRepository;

    /**
     * @var \TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository
     */
    protected $backendUserGroupRepository;

    /**
     * @var \TYPO3\CMS\Beuser\Domain\Repository\BackendUserSessionRepository
     */
    protected $backendUserSessionRepository;

    /**
     * @param \TYPO3\CMS\Beuser\Service\ModuleDataStorageService $moduleDataStorageService
     */
    public function injectModuleDataStorageService(\TYPO3\CMS\Beuser\Service\ModuleDataStorageService $moduleDataStorageService)
    {
        $this->moduleDataStorageService = $moduleDataStorageService;
    }

    /**
     * @param \TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository $backendUserRepository
     */
    public function injectBackendUserRepository(\TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository $backendUserRepository)
    {
        $this->backendUserRepository = $backendUserRepository;
    }

    /**
     * @param \TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository $backendUserGroupRepository
     */
    public function injectBackendUserGroupRepository(\TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository $backendUserGroupRepository)
    {
        $this->backendUserGroupRepository = $backendUserGroupRepository;
    }

    /**
     * @param \TYPO3\CMS\Beuser\Domain\Repository\BackendUserSessionRepository $backendUserSessionRepository
     */
    public function injectBackendUserSessionRepository(\TYPO3\CMS\Beuser\Domain\Repository\BackendUserSessionRepository $backendUserSessionRepository)
    {
        $this->backendUserSessionRepository = $backendUserSessionRepository;
    }

    /**
     * Load and persist module data
     *
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request
     * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function processRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response)
    {
        $this->moduleData = $this->moduleDataStorageService->loadModuleData();
        // We "finally" persist the module data.
        try {
            parent::processRequest($request, $response);
            $this->moduleDataStorageService->persistModuleData($this->moduleData);
        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $e) {
            $this->moduleDataStorageService->persistModuleData($this->moduleData);
            throw $e;
        }
    }

    /**
     * Initialize actions
     *
     * @return void
     * @throws \RuntimeException
     */
    public function initializeAction()
    {
        // @TODO: Extbase backend modules relies on frontend TypoScript for view, persistence
        // and settings. Thus, we need a TypoScript root template, that then loads the
        // ext_typoscript_setup.txt file of this module. This is nasty, but can not be
        // circumvented until there is a better solution in extbase.
        // For now we throw an exception if no settings are detected.
        if (empty($this->settings)) {
            throw new \RuntimeException('No settings detected. This module can not work then. This usually happens if there is no frontend TypoScript template with root flag set. ' . 'Please create a frontend page with a TypoScript root template.', 1344375003);
        }
    }

    /**
     * Displays all BackendUsers
     * - Switch session to different user
     *
     * @param \TYPO3\CMS\Beuser\Domain\Model\Demand $demand
     * @return void
     */
    public function indexAction(\TYPO3\CMS\Beuser\Domain\Model\Demand $demand = null)
    {
        if ($demand === null) {
            $demand = $this->moduleData->getDemand();
        } else {
            $this->moduleData->setDemand($demand);
        }
        // Switch user until logout
        $switchUser = (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SwitchUser');
        if ($switchUser > 0) {
            $this->switchUser($switchUser);
        }
        $compareUserList = $this->moduleData->getCompareUserList();

        // Create online user list for easy parsing
        $onlineUsers = $this->backendUserSessionRepository->findAllActive();
        $onlineBackendUsers = [];
        if (is_array($onlineUsers)) {
            foreach ($onlineUsers as $onlineUser) {
                $onlineBackendUsers[$onlineUser['ses_userid']] = true;
            }
        }
        $this->view->assign('onlineBackendUsers', $onlineBackendUsers);

        $this->view->assign('demand', $demand);
        $this->view->assign('returnUrl', rawurlencode(BackendUtility::getModuleUrl('system_BeuserTxBeuser')));
        $this->view->assign('dateFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy']);
        $this->view->assign('timeFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm']);
        $this->view->assign('backendUsers', $this->backendUserRepository->findDemanded($demand));
        $this->view->assign('backendUserGroups', array_merge([''], $this->backendUserGroupRepository->findAll()->toArray()));
        $this->view->assign('compareUserList', !empty($compareUserList) ? $this->backendUserRepository->findByUidList($compareUserList) : '');
    }

    /**
     * Views all currently logged in BackendUsers and their sessions
     *
     * @return void
     */
    public function onlineAction()
    {
        $onlineUsersAndSessions = [];
        $onlineUsers = $this->backendUserRepository->findOnline();
        foreach ($onlineUsers as $onlineUser) {
            $onlineUsersAndSessions[] = [
                'backendUser' => $onlineUser,
                'sessions' => $this->backendUserSessionRepository->findByBackendUser($onlineUser)
            ];
        }
        $this->view->assign('dateFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy']);
        $this->view->assign('timeFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm']);
        $this->view->assign('onlineUsersAndSessions', $onlineUsersAndSessions);
        $this->view->assign('currentSessionId', $this->getBackendUserAuthentication()->user['ses_id']);
    }

    /**
     * Compare backend users from demand
     *
     * @return void
     */
    public function compareAction()
    {
        $compareUserList = $this->moduleData->getCompareUserList();
        $this->view->assign('dateFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy']);
        $this->view->assign('timeFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm']);
        $this->view->assign('compareUserList', !empty($compareUserList) ? $this->backendUserRepository->findByUidList($compareUserList) : '');
    }

    /**
     * Attaches one backend user to the compare list
     *
     * @param int $uid
     * @return void
     */
    public function addToCompareListAction($uid)
    {
        $this->moduleData->attachUidCompareUser($uid);
        $this->moduleDataStorageService->persistModuleData($this->moduleData);
        $this->forward('index');
    }

    /**
     * Removes given backend user to the compare list
     *
     * @param int $uid
     * @return void
     */
    public function removeFromCompareListAction($uid)
    {
        $this->moduleData->detachUidCompareUser($uid);
        $this->moduleDataStorageService->persistModuleData($this->moduleData);
        $this->forward('index');
    }

    /**
     * Terminate BackendUser session and logout corresponding client
     * Redirects to onlineAction with message
     *
     * @param \TYPO3\CMS\Beuser\Domain\Model\BackendUser $backendUser
     * @param string $sessionId
     * @return void
     */
    protected function terminateBackendUserSessionAction(\TYPO3\CMS\Beuser\Domain\Model\BackendUser $backendUser, $sessionId)
    {
        $this->getDatabaseConnection()->exec_DELETEquery(
            'be_sessions',
            'ses_userid = "' . (int)$backendUser->getUid() . '" AND ses_id = ' . $this->getDatabaseConnection()->fullQuoteStr($sessionId, 'be_sessions') . ' LIMIT 1'
        );
        if ($this->getDatabaseConnection()->sql_affected_rows() == 1) {
            $this->addFlashMessage(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:terminateSessionSuccess', 'beuser'));
        }
        $this->forward('online');
    }

    /**
     * Switches to a given user (SU-mode) and then redirects to the start page of the backend to refresh the navigation etc.
     *
     * @param string $switchUser BE-user record that will be switched to
     * @return void
     */
    protected function switchUser($switchUser)
    {
        $targetUser = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('be_users', $switchUser);
        if (is_array($targetUser) && $this->getBackendUserAuthentication()->isAdmin()) {
            $updateData['ses_userid'] = (int)$targetUser['uid'];
            $updateData['ses_backuserid'] = (int)$this->getBackendUserAuthentication()->user['uid'];

            // Set backend user listing module as starting module for switchback
            $this->getBackendUserAuthentication()->uc['startModuleOnFirstLogin'] = 'system_BeuserTxBeuser';
            $this->getBackendUserAuthentication()->writeUC();

            $whereClause = 'ses_id=' . $this->getDatabaseConnection()->fullQuoteStr($this->getBackendUserAuthentication()->id, 'be_sessions');
            $whereClause .= ' AND ses_name=' . $this->getDatabaseConnection()->fullQuoteStr(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::getCookieName(), 'be_sessions');
            $whereClause .= ' AND ses_userid=' . (int)$this->getBackendUserAuthentication()->user['uid'];

            $this->getDatabaseConnection()->exec_UPDATEquery(
                'be_sessions',
                $whereClause,
                $updateData
            );

            $redirectUrl = 'index.php' . ($GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces'] ? '' : '?commandLI=1');
            \TYPO3\CMS\Core\Utility\HttpUtility::redirect($redirectUrl);
        }
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
