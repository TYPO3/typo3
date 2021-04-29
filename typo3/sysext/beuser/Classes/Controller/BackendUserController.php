<?php

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

namespace TYPO3\CMS\Beuser\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Authentication\Event\SwitchUserEvent;
use TYPO3\CMS\Backend\Authentication\PasswordReset;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\CMS\Beuser\Domain\Model\Demand;
use TYPO3\CMS\Beuser\Domain\Model\ModuleData;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserSessionRepository;
use TYPO3\CMS\Beuser\Service\UserInformationService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Backend module user administration controller
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BackendUserController extends ActionController
{
    /**
     * @var int
     */
    const RECENT_USERS_LIMIT = 3;

    protected ?ModuleData $moduleData = null;
    protected BackendUserRepository $backendUserRepository;
    protected BackendUserGroupRepository $backendUserGroupRepository;
    protected BackendUserSessionRepository $backendUserSessionRepository;
    protected UserInformationService $userInformationService;

    public function __construct(
        BackendUserRepository $backendUserRepository,
        BackendUserGroupRepository $backendUserGroupRepository,
        BackendUserSessionRepository $backendUserSessionRepository,
        UserInformationService $userInformationService
    ) {
        $this->backendUserRepository = $backendUserRepository;
        $this->backendUserGroupRepository = $backendUserGroupRepository;
        $this->backendUserSessionRepository = $backendUserSessionRepository;
        $this->userInformationService = $userInformationService;
    }

    /**
     * Init module state.
     * This isn't done within __construct() since the controller
     * object is only created once in extbase when multiple actions are called in
     * one call. When those change module state, the second action would see old state.
     */
    public function initializeAction(): void
    {
        $this->moduleData = ModuleData::fromUc((array)($this->getBackendUser()->getModuleData('tx_beuser')));
    }

    /**
     * Assign default variables to view
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view): void
    {
        $view->assignMultiple([
            'shortcutLabel' => 'backendUsers',
            'route' => $this->getRequest()->getAttribute('route')->getPath(),
            'action' => $this->request->getControllerActionName(),
            'controller' => $this->request->getControllerName(),
            'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
            'timeFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
        ]);
    }

    /**
     * Displays all BackendUsers
     * - Switch session to different user
     *
     * @param Demand|null $demand
     * @param int $currentPage
     * @param string $operation
     * @return ResponseInterface
     */
    public function indexAction(Demand $demand = null, int $currentPage = 1, string $operation = ''): ResponseInterface
    {
        $backendUser = $this->getBackendUser();

        if ($operation === 'reset-filters') {
            // Reset the module data demand object
            $this->moduleData->setDemand(new Demand());
            $demand = null;
        }
        if ($demand === null) {
            $demand = $this->moduleData->getDemand();
        } else {
            $this->moduleData->setDemand($demand);
        }
        $backendUser->pushModuleData('tx_beuser', $this->moduleData->forUc());

        // Switch user until logout
        $switchUser = (int)GeneralUtility::_GP('SwitchUser');
        if ($switchUser > 0) {
            $this->switchUser($switchUser);
        }
        $compareUserList = $this->moduleData->getCompareUserList();

        $backendUsers = $this->backendUserRepository->findDemanded($demand);
        $paginator = new QueryResultPaginator($backendUsers, $currentPage, 50);
        $pagination = new SimplePagination($paginator);

        $this->view->assignMultiple([
            'onlineBackendUsers' => $this->getOnlineBackendUsers(),
            'demand' => $demand,
            'paginator' => $paginator,
            'pagination' => $pagination,
            'totalAmountOfBackendUsers' => $backendUsers->count(),
            'backendUserGroups' => array_merge([''], $this->backendUserGroupRepository->findAll()->toArray()),
            'compareUserUidList' => array_combine($compareUserList, $compareUserList),
            'currentUserUid' => $backendUser->user['uid'],
            'compareUserList' => !empty($compareUserList) ? $this->backendUserRepository->findByUidList($compareUserList) : '',
        ]);

        return $this->htmlResponse($this->view->render());
    }

    /**
     * Views all currently logged in BackendUsers and their sessions
     */
    public function onlineAction(): ResponseInterface
    {
        $onlineUsersAndSessions = [];
        $onlineUsers = $this->backendUserRepository->findOnline();
        foreach ($onlineUsers as $onlineUser) {
            $onlineUsersAndSessions[] = [
                'backendUser' => $onlineUser,
                'sessions' => $this->backendUserSessionRepository->findByBackendUser($onlineUser)
            ];
        }

        $currentSessionId = $this->backendUserSessionRepository->getPersistedSessionIdentifier($this->getBackendUser());

        $this->view->assignMultiple([
            'shortcutLabel' => 'onlineUsers',
            'onlineUsersAndSessions' => $onlineUsersAndSessions,
            'currentSessionId' => $currentSessionId,
        ]);

        return $this->htmlResponse($this->view->render());
    }

    public function showAction(int $uid = 0): ResponseInterface
    {
        $data = $this->userInformationService->getUserInformation($uid);
        $this->view->assignMultiple([
            'shortcutLabel' => 'showUser',
            'data' => $data
        ]);

        return $this->htmlResponse($this->view->render());
    }

    /**
     * Compare backend users from demand
     */
    public function compareAction(): ResponseInterface
    {
        $compareUserList = $this->moduleData->getCompareUserList();
        if (empty($compareUserList)) {
            $this->redirect('index');
        }

        $compareData = [];
        foreach ($compareUserList as $uid) {
            if ($compareInformation = $this->userInformationService->getUserInformation($uid)) {
                $compareData[] = $compareInformation;
            }
        }

        $this->view->assignMultiple([
            'shortcutLabel' => 'compareUsers',
            'compareUserList' => $compareData,
            'onlineBackendUsers' => $this->getOnlineBackendUsers()
        ]);

        return $this->htmlResponse($this->view->render());
    }

    /**
     * Starts the password reset process for a selected user.
     *
     * @param int $user
     */
    public function initiatePasswordResetAction(int $user): ResponseInterface
    {
        $context = GeneralUtility::makeInstance(Context::class);
        /** @var BackendUser $user */
        $user = $this->backendUserRepository->findByUid($user);
        if (!$user || !$user->isPasswordResetEnabled() || !$context->getAspect('backend.user')->isAdmin()) {
            // Add an error message
            $this->addFlashMessage(
                LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:flashMessage.resetPassword.error.text', 'beuser') ?? '',
                LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:flashMessage.resetPassword.error.title', 'beuser') ?? '',
                FlashMessage::ERROR
            );
        } else {
            GeneralUtility::makeInstance(PasswordReset::class)->initiateReset(
                $this->getRequest(),
                $context,
                $user->getEmail()
            );
            $this->addFlashMessage(
                LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:flashMessage.resetPassword.success.text', 'beuser', [$user->getEmail()]) ?? '',
                LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:flashMessage.resetPassword.success.title', 'beuser') ?? '',
                FlashMessage::OK
            );
        }
        return new ForwardResponse('index');
    }

    /**
     * Attaches one backend user to the compare list
     *
     * @param int $uid
     */
    public function addToCompareListAction($uid)
    {
        $this->moduleData->attachUidCompareUser($uid);
        $this->getBackendUser()->pushModuleData('tx_beuser', $this->moduleData->forUc());
        return new ForwardResponse('index');
    }

    /**
     * Removes given backend user to the compare list
     *
     * @param int $uid
     * @param int $redirectToCompare
     */
    public function removeFromCompareListAction($uid, int $redirectToCompare = 0)
    {
        $this->moduleData->detachUidCompareUser($uid);
        $this->getBackendUser()->pushModuleData('tx_beuser', $this->moduleData->forUc());
        if ($redirectToCompare) {
            $this->redirect('compare');
        } else {
            $this->redirect('index');
        }
    }

    /**
     * Removes all backend users from the compare list
     * @throws StopActionException
     */
    public function removeAllFromCompareListAction(): void
    {
        $this->moduleData->resetCompareUserList();
        $this->getBackendUser()->pushModuleData('tx_beuser', $this->moduleData->forUc());
        $this->redirect('index');
    }

    /**
     * Terminate BackendUser session and logout corresponding client
     * Redirects to onlineAction with message
     *
     * @param BackendUser $backendUser
     * @param string $sessionId
     */
    protected function terminateBackendUserSessionAction(BackendUser $backendUser, $sessionId)
    {
        // terminating value of persisted session ID
        $success = $this->backendUserSessionRepository->terminateSessionByIdentifier($sessionId);
        if ($success) {
            $this->addFlashMessage(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:terminateSessionSuccess', 'beuser') ?? '');
        }
        return new ForwardResponse('online');
    }

    /**
     * Switches to a given user (SU-mode) and then redirects to the start page of the backend to refresh the navigation etc.
     *
     * @param int $switchUser BE-user record that will be switched to
     */
    protected function switchUser($switchUser)
    {
        $backendUser = $this->getBackendUser();
        $targetUser = BackendUtility::getRecord('be_users', $switchUser);
        if (is_array($targetUser) && $backendUser->isAdmin()) {
            // Set backend user listing module as starting module for switchback
            $backendUser->uc['startModuleOnFirstLogin'] = 'system_BeuserTxBeuser';
            $backendUser->uc['recentSwitchedToUsers'] = $this->generateListOfMostRecentSwitchedUsers($targetUser['uid']);
            $backendUser->writeUC();

            // User switch   written to log
            $backendUser->writelog(
                255,
                2,
                0,
                1,
                'User %s switched to user %s (be_users:%s)',
                [
                    $backendUser->user['username'],
                    $targetUser['username'],
                    $targetUser['uid'],
                ]
            );

            $this->backendUserSessionRepository->switchToUser($backendUser, (int)$targetUser['uid']);

            $event = new SwitchUserEvent(
                $backendUser->getSession()->getIdentifier(),
                $targetUser,
                (array)$backendUser->user
            );
            $this->eventDispatcher->dispatch($event);

            $redirectUri = GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
                'main',
                $GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces'] ? [] : ['commandLI' => '1']
            );
            throw new PropagateResponseException(new RedirectResponse($redirectUri, 303), 1607271592);
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
        $backendUser = $this->getBackendUser();

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
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return ServerRequestInterface
     */
    protected function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }

    /**
     * Create an array with the uids of online users as the keys
     * [
     *   1 => true,
     *   5 => true
     * ]
     * @return array
     */
    protected function getOnlineBackendUsers(): array
    {
        $onlineUsers = $this->backendUserSessionRepository->findAllActive();
        $onlineBackendUsers = [];
        foreach ($onlineUsers as $onlineUser) {
            $onlineBackendUsers[$onlineUser['ses_userid']] = true;
        }
        return $onlineBackendUsers;
    }
}
