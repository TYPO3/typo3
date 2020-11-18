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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Session\Backend\HashableSessionBackendInterface;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Backend module user administration controller
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BackendUserController extends ActionController
{
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'initializeView' => 'Using BackendUserController::initializeView() is deprecated and will not be possible anymore in TYPO3 v10.0.',
    ];

    /**
     * @var int
     */
    const RECENT_USERS_LIMIT = 3;

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
     * Assign default variables to view
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        $view->assignMultiple([
            'shortcutLabel' => 'backendUsers',
            'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
            'timeFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
        ]);
    }

    /**
     * Displays all BackendUsers
     * - Switch session to different user
     *
     * @param \TYPO3\CMS\Beuser\Domain\Model\Demand $demand
     */
    public function indexAction(\TYPO3\CMS\Beuser\Domain\Model\Demand $demand = null)
    {
        if ($demand === null) {
            $demand = $this->moduleData->getDemand();
        } else {
            $this->moduleData->setDemand($demand);
        }
        // Switch user until logout
        $switchUser = (int)GeneralUtility::_GP('SwitchUser');
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

        $this->view->assignMultiple([
            'onlineBackendUsers' => $onlineBackendUsers,
            'demand' => $demand,
            'backendUsers' => $this->backendUserRepository->findDemanded($demand),
            'backendUserGroups' => array_merge([''], $this->backendUserGroupRepository->findAll()->toArray()),
            'compareUserUidList' => array_combine($compareUserList, $compareUserList),
            'currentUserUid' => $this->getBackendUserAuthentication()->user['uid'],
            'compareUserList' => !empty($compareUserList) ? $this->backendUserRepository->findByUidList($compareUserList) : '',
        ]);
    }

    /**
     * Views all currently logged in BackendUsers and their sessions
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

        $currentSessionId = $this->getBackendUserAuthentication()->getSessionId();
        $sessionBackend = $this->getSessionBackend();
        if ($sessionBackend instanceof HashableSessionBackendInterface) {
            $currentSessionId = $sessionBackend->hash($currentSessionId);
        }
        $this->view->assignMultiple([
            'shortcutLabel' => 'onlineUsers',
            'onlineUsersAndSessions' => $onlineUsersAndSessions,
            'currentSessionId' => $currentSessionId,
        ]);
    }

    /**
     * Compare backend users from demand
     */
    public function compareAction()
    {
        $compareUserList = $this->moduleData->getCompareUserList();
        if (empty($compareUserList)) {
            $this->redirect('index');
        }

        $this->view->assignMultiple([
            'shortcutLabel' => 'compareUsers',
            'compareUserList' => $this->backendUserRepository->findByUidList($compareUserList),
        ]);
    }

    /**
     * Attaches one backend user to the compare list
     *
     * @param int $uid
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
     */
    protected function terminateBackendUserSessionAction(\TYPO3\CMS\Beuser\Domain\Model\BackendUser $backendUser, $sessionId)
    {
        // terminating value of persisted session ID (probably hashed value)
        $sessionBackend = $this->getSessionBackend();
        $success = $sessionBackend->remove($sessionId);

        if ($success) {
            $this->addFlashMessage(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:terminateSessionSuccess', 'beuser'));
        }
        $this->forward('online');
    }

    /**
     * Switches to a given user (SU-mode) and then redirects to the start page of the backend to refresh the navigation etc.
     *
     * @param string $switchUser BE-user record that will be switched to
     */
    protected function switchUser($switchUser)
    {
        $targetUser = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('be_users', $switchUser);
        if (is_array($targetUser) && $this->getBackendUserAuthentication()->isAdmin()) {
            // Set backend user listing module as starting module for switchback
            $this->getBackendUserAuthentication()->uc['startModuleOnFirstLogin'] = 'system_BeuserTxBeuser';
            $this->getBackendUserAuthentication()->uc['recentSwitchedToUsers'] = $this->generateListOfMostRecentSwitchedUsers($targetUser['uid']);
            $this->getBackendUserAuthentication()->writeUC();

            $this->getSessionBackend()->update(
                $this->getBackendUserAuthentication()->getSessionId(),
                [
                    'ses_userid' => (int)$targetUser['uid'],
                    'ses_backuserid' => (int)$this->getBackendUserAuthentication()->user['uid']
                ]
            );

            $this->emitSwitchUserSignal($targetUser);

            $redirectUrl = 'index.php' . ($GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces'] ? '' : '?commandLI=1');
            \TYPO3\CMS\Core\Utility\HttpUtility::redirect($redirectUrl);
        }
    }

    /**
     * Generates a list of users to whom where switched in the past. This is limited by RECENT_USERS_LIMIT.
     *
     * @param int $targetUserUid
     * @return int[]
     */
    protected function generateListOfMostRecentSwitchedUsers(int $targetUserUid): array
    {
        $latestUserUids = [];
        $backendUser = $this->getBackendUserAuthentication();

        if (isset($backendUser->uc['recentSwitchedToUsers']) && is_array($backendUser->uc['recentSwitchedToUsers'])) {
            $latestUserUids = $backendUser->uc['recentSwitchedToUsers'];
        }

        // Remove potentially existing user in that list
        $index = array_search($targetUserUid, $latestUserUids, true);
        if ($index !== false) {
            unset($latestUserUids[$index]);
        }

        array_unshift($latestUserUids, $targetUserUid);
        $latestUserUids = array_slice($latestUserUids, 0, static::RECENT_USERS_LIMIT);

        return $latestUserUids;
    }

    /**
     * Emit a signal when using the "switch to user" functionality
     *
     * @param array $targetUser
     */
    protected function emitSwitchUserSignal(array $targetUser)
    {
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'switchUser', [$targetUser]);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return SessionBackendInterface
     */
    protected function getSessionBackend()
    {
        $loginType = $this->getBackendUserAuthentication()->getLoginType();
        return GeneralUtility::makeInstance(SessionManager::class)->getSessionBackend($loginType);
    }
}
