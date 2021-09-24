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
use TYPO3\CMS\Backend\Authentication\PasswordReset;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\CMS\Beuser\Domain\Model\Demand;
use TYPO3\CMS\Beuser\Domain\Model\ModuleData;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserSessionRepository;
use TYPO3\CMS\Beuser\Service\UserInformationService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Backend module user and user group administration controller
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BackendUserController extends ActionController
{
    protected ?ModuleData $moduleData = null;
    protected ?ModuleTemplate $moduleTemplate = null;
    protected BackendUserRepository $backendUserRepository;
    protected BackendUserGroupRepository $backendUserGroupRepository;
    protected BackendUserSessionRepository $backendUserSessionRepository;
    protected UserInformationService $userInformationService;
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected BackendUriBuilder $backendUriBuilder;
    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;

    public function __construct(
        BackendUserRepository $backendUserRepository,
        BackendUserGroupRepository $backendUserGroupRepository,
        BackendUserSessionRepository $backendUserSessionRepository,
        UserInformationService $userInformationService,
        ModuleTemplateFactory $moduleTemplateFactory,
        BackendUriBuilder $backendUriBuilder,
        IconFactory $iconFactory,
        PageRenderer $pageRenderer
    ) {
        $this->backendUserRepository = $backendUserRepository;
        $this->backendUserGroupRepository = $backendUserGroupRepository;
        $this->backendUserSessionRepository = $backendUserSessionRepository;
        $this->userInformationService = $userInformationService;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->backendUriBuilder = $backendUriBuilder;
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
    }

    /**
     * Override the default action if found in user uc
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function processRequest(RequestInterface $request): ResponseInterface
    {
        $arguments = $request->getArguments();
        $backendUser = $this->getBackendUser();
        if (is_array($arguments) && isset($arguments['action']) && in_array((string)$arguments['action'], ['index', 'groups', 'online'])
            && (string)($backendUser->uc['beuser']['defaultAction'] ?? '') !== (string)$arguments['action']
        ) {
            $backendUser->uc['beuser']['defaultAction'] = (string)$arguments['action'];
            $backendUser->writeUC();
        } elseif (!isset($arguments['action']) && isset($backendUser->uc['beuser']['defaultAction'])
            && in_array((string)$backendUser->uc['beuser']['defaultAction'], ['index', 'groups', 'online'])
        ) {
            if ($request instanceof Request) {
                $request->setControllerActionName((string)$backendUser->uc['beuser']['defaultAction']);
            }
        }
        return parent::processRequest($request);
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
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->setTitle(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'));
    }

    /**
     * Assign default variables to view
     * @param ViewInterface $view
     * @todo v12: Change signature to TYPO3Fluid\Fluid\View\ViewInterface when extbase ViewInterface is dropped.
     */
    protected function initializeView(ViewInterface $view): void
    {
        $view->assignMultiple([
            'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
            'timeFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
        ]);

        // Load requireJS modules
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Beuser/BackendUserListing');
    }

    /**
     * Displays all BackendUsers
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

        $this->addMainMenu('index');
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $addUserButton = $buttonBar->makeLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL))
            ->setTitle(LocalizationUtility::translate('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newRecordGeneral'))
            ->setHref($this->backendUriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['be_users' => [0 => 'new']],
                'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
            ]));
        $buttonBar->addButton($addUserButton);
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('system_BeuserTxBeuser')
            ->setArguments(['tx_beuser_system_beusertxbeuser' => ['action' => 'index']])
            ->setDisplayName(LocalizationUtility::translate('backendUsers', 'beuser'));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/SwitchUser');

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
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
                'sessions' => $this->backendUserSessionRepository->findByBackendUser($onlineUser),
            ];
        }

        $currentSessionId = $this->backendUserSessionRepository->getPersistedSessionIdentifier($this->getBackendUser());

        $this->view->assignMultiple([
            'onlineUsersAndSessions' => $onlineUsersAndSessions,
            'currentSessionId' => $currentSessionId,
        ]);

        $this->addMainMenu('online');
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('system_BeuserTxBeuser')
            ->setArguments(['tx_beuser_system_beusertxbeuser' => ['action' => 'online']])
            ->setDisplayName(LocalizationUtility::translate('onlineUsers', 'beuser'));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    public function showAction(int $uid = 0): ResponseInterface
    {
        $data = $this->userInformationService->getUserInformation($uid);
        $this->view->assign('data', $data);

        $this->addMainMenu('show');
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $backButton = $buttonBar->makeLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL))
            ->setTitle(LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
            ->setHref($this->backendUriBuilder->buildUriFromRoute('system_BeuserTxBeuser'));
        $buttonBar->addButton($backButton);
        $editButton = $buttonBar->makeLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL))
            ->setTitle(LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
            ->setHref($this->backendUriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['be_users' => [$uid => 'edit']],
                'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
            ]));
        $buttonBar->addButton($editButton);
        $addUserButton = $buttonBar->makeLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL))
            ->setTitle(LocalizationUtility::translate('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newRecordGeneral'))
            ->setHref($this->backendUriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['be_users' => [0 => 'new']],
                'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
            ]));
        $buttonBar->addButton($addUserButton);
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('system_BeuserTxBeuser')
            ->setArguments(['tx_beuser_system_beusertxbeuser' => ['action' => 'show', 'uid' => $uid]])
            ->setDisplayName(LocalizationUtility::translate('backendUser', 'beuser') . ': ' . (string)$data['user']['username']);
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
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
            'compareUserList' => $compareData,
            'onlineBackendUsers' => $this->getOnlineBackendUsers(),
        ]);

        $this->addMainMenu('compare');
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $backButton = $buttonBar->makeLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL))
            ->setTitle(LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
            ->setHref($this->backendUriBuilder->buildUriFromRoute('system_BeuserTxBeuser'));
        $buttonBar->addButton($backButton);
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('system_BeuserTxBeuser')
            ->setArguments(['tx_beuser_system_beusertxbeuser' => ['action' => 'compare']])
            ->setDisplayName(LocalizationUtility::translate('compareUsers', 'beuser'));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
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
                $this->request,
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
    public function addToCompareListAction($uid): ResponseInterface
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
    public function removeFromCompareListAction($uid, int $redirectToCompare = 0): void
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
     * @param string $sessionId
     */
    protected function terminateBackendUserSessionAction($sessionId): ResponseInterface
    {
        // terminating value of persisted session ID
        $success = $this->backendUserSessionRepository->terminateSessionByIdentifier($sessionId);
        if ($success) {
            $this->addFlashMessage(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:terminateSessionSuccess', 'beuser') ?? '');
        }
        return new ForwardResponse('online');
    }

    /**
     * Displays all BackendUserGroups
     *
     * @param int $currentPage
     * @return ResponseInterface
     */
    public function groupsAction(int $currentPage = 1): ResponseInterface
    {
        /** @var QueryResultInterface $backendUsers */
        $backendUsers = $this->backendUserGroupRepository->findAll();
        $paginator = new QueryResultPaginator($backendUsers, $currentPage, 50);
        $pagination = new SimplePagination($paginator);
        $compareGroupUidList = array_keys($this->getBackendUser()->uc['beuser']['compareGroupUidList'] ?? []);
        $this->view->assignMultiple(
            [
                'paginator' => $paginator,
                'pagination' => $pagination,
                'totalAmountOfBackendUserGroups' => $backendUsers->count(),
                'compareGroupUidList' => array_map(static function ($value) { // uid as key and force value to 1
                    return 1;
                }, array_flip($compareGroupUidList)),
                'compareGroupList' => !empty($compareGroupUidList) ? $this->backendUserGroupRepository->findByUidList($compareGroupUidList) : [],
            ]
        );

        $this->addMainMenu('groups');
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $addGroupButton = $buttonBar->makeLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL))
            ->setTitle(LocalizationUtility::translate('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newRecordGeneral'))
            ->setHref($this->backendUriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['be_groups' => [0 => 'new']],
                'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
            ]));
        $buttonBar->addButton($addGroupButton);
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('system_BeuserTxBeuser')
            ->setArguments(['tx_beuser_system_beusertxbeuser' => ['action' => 'groups']])
            ->setDisplayName(LocalizationUtility::translate('backendUserGroupsMenu', 'beuser'));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    public function compareGroupsAction(): ResponseInterface
    {
        $compareGroupUidList = array_keys($this->getBackendUser()->uc['beuser']['compareGroupUidList'] ?? []);

        $compareData = [];
        foreach ($compareGroupUidList as $uid) {
            if ($compareInformation = $this->userInformationService->getGroupInformation($uid)) {
                $compareData[] = $compareInformation;
            }
        }
        if (empty($compareData)) {
            $this->redirect('groups');
        }

        $this->view->assign('compareGroupList', $compareData);

        $this->addMainMenu('compareGroups');
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $backButton = $buttonBar->makeLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL))
            ->setTitle(LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
            ->setHref($this->uriBuilder->uriFor('groups'));
        $buttonBar->addButton($backButton);
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('system_BeuserTxBeuser')
            ->setArguments(['tx_beuser_system_beusertxbeuser' => ['action' => 'compareGroups']])
            ->setDisplayName(LocalizationUtility::translate('compareBackendUsersGroups', 'beuser'));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Attaches one backend user group to the compare list
     *
     * @param int $uid
     */
    public function addGroupToCompareListAction(int $uid): void
    {
        $backendUser = $this->getBackendUser();
        $list = $backendUser->uc['beuser']['compareGroupUidList'] ?? [];
        $list[$uid] = true;
        $backendUser->uc['beuser']['compareGroupUidList'] = $list;
        $backendUser->writeUC();
        $this->redirect('groups');
    }

    /**
     * Removes given backend user group to the compare list
     *
     * @param int $uid
     * @param int $redirectToCompare
     */
    public function removeGroupFromCompareListAction(int $uid, int $redirectToCompare = 0): void
    {
        $backendUser = $this->getBackendUser();
        $list = $backendUser->uc['beuser']['compareGroupUidList'] ?? [];
        unset($list[$uid]);
        $backendUser->uc['beuser']['compareGroupUidList'] = $list;
        $backendUser->writeUC();

        if ($redirectToCompare) {
            $this->redirect('compareGroups');
        } else {
            $this->redirect('groups');
        }
    }

    /**
     * Removes all backend user groups from the compare list
     */
    public function removeAllGroupsFromCompareListAction(): void
    {
        $backendUser = $this->getBackendUser();
        $backendUser->uc['beuser']['compareGroupUidList'] = [];
        $backendUser->writeUC();
        $this->redirect('groups');
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

    /**
     * Doc header main drop down
     *
     * @param string $currentAction
     */
    protected function addMainMenu(string $currentAction): void
    {
        $this->uriBuilder->setRequest($this->request);
        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('BackendUserModuleMenu');
        $menu->addMenuItem(
            $menu->makeMenuItem()
            ->setTitle(LocalizationUtility::translate('backendUsers', 'beuser'))
            ->setHref($this->uriBuilder->uriFor('index'))
            ->setActive($currentAction === 'index')
        );
        if ($currentAction === 'show') {
            $menu->addMenuItem(
                $menu->makeMenuItem()
                ->setTitle(LocalizationUtility::translate('backendUserDetails', 'beuser'))
                ->setHref($this->uriBuilder->uriFor('show'))
                ->setActive(true)
            );
        }
        if ($currentAction === 'compare') {
            $menu->addMenuItem(
                $menu->makeMenuItem()
                ->setTitle(LocalizationUtility::translate('compareBackendUsers', 'beuser'))
                ->setHref($this->uriBuilder->uriFor('index'))
                ->setActive(true)
            );
        }
        $menu->addMenuItem(
            $menu->makeMenuItem()
            ->setTitle(LocalizationUtility::translate('backendUserGroupsMenu', 'beuser'))
            ->setHref($this->uriBuilder->uriFor('groups'))
            ->setActive($currentAction === 'groups')
        );
        if ($currentAction === 'compareGroups') {
            $menu->addMenuItem(
                $menu->makeMenuItem()
                ->setTitle(LocalizationUtility::translate('compareBackendUsersGroups', 'beuser'))
                ->setHref($this->uriBuilder->uriFor('compareGroups'))
                ->setActive(true)
            );
        }
        $menu->addMenuItem(
            $menu->makeMenuItem()
            ->setTitle(LocalizationUtility::translate('onlineUsers', 'beuser'))
            ->setHref($this->uriBuilder->uriFor('online'))
            ->setActive($currentAction === 'online')
        );
        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
