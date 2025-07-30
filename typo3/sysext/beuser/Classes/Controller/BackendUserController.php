<?php

declare(strict_types=1);

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
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Beuser\Domain\Dto\BackendUserGroup;
use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\CMS\Beuser\Domain\Model\Demand;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserSessionRepository;
use TYPO3\CMS\Beuser\Domain\Repository\FileMountRepository;
use TYPO3\CMS\Beuser\Event\AfterBackendGroupFilterListIsAssembledEvent;
use TYPO3\CMS\Beuser\Event\AfterFilemountsListIsAssembledEvent;
use TYPO3\CMS\Beuser\Service\UserInformationService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\AllowedMethodsTrait;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Backend module user and user group administration controller.
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BackendUserController extends ActionController
{
    use AllowedMethodsTrait;

    protected ?ModuleData $moduleData = null;
    protected ModuleTemplate $moduleTemplate;

    public function __construct(
        protected readonly ComponentFactory $componentFactory,
        protected readonly BackendUserRepository $backendUserRepository,
        protected readonly BackendUserGroupRepository $backendUserGroupRepository,
        protected readonly BackendUserSessionRepository $backendUserSessionRepository,
        protected readonly UserInformationService $userInformationService,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly BackendUriBuilder $backendUriBuilder,
        protected readonly IconFactory $iconFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly FileMountRepository $fileMountRepository
    ) {}

    /**
     * Store a valid selected action as defaultAction
     */
    public function processRequest(RequestInterface $request): ResponseInterface
    {
        /** @var Request $request */
        $arguments = $request->getArguments();
        $moduleData = $request->getAttribute('moduleData');

        if (in_array((string)($arguments['action'] ?? ''), ['list', 'groups', 'online', 'filemounts'], true)
            && (string)$moduleData->get('defaultAction') !== (string)$arguments['action']
        ) {
            $moduleData->set('defaultAction', (string)$arguments['action']);
            $this->getBackendUser()->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
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
        $this->moduleData = $this->request->getAttribute('moduleData');
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->setTitle(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'));
        $this->moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());
    }

    /**
     * Assign default variables to ModuleTemplate view
     */
    protected function initializeView(): void
    {
        $this->moduleTemplate->assignMultiple([
            'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
            'timeFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
        ]);
        // Load JavaScript modules
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/context-menu.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/modal.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/beuser/backend-user-listing.js');
    }

    /**
     * Default action, forwarding the request to either a defined default action or the default entry point "list"
     */
    public function indexAction(): ResponseInterface
    {
        $moduleData = $this->request->getAttribute('moduleData');

        if ($moduleData->has('defaultAction')
            && in_array((string)$moduleData->get('defaultAction'), ['list', 'groups', 'online', 'filemounts'], true)
        ) {
            return new ForwardResponse((string)$moduleData->get('defaultAction'));
        }

        return new ForwardResponse('list');
    }

    /**
     * Displays all BackendUsers
     */
    public function listAction(?Demand $demand = null, int $currentPage = 1, string $operation = ''): ResponseInterface
    {
        $backendUser = $this->getBackendUser();

        if ($operation === 'reset-filters') {
            // Reset the module data demand object
            $this->moduleData->set('demand', []);
            $demand = null;
        }
        if ($demand === null) {
            $demand = Demand::fromUc((array)$this->moduleData->get('demand', []));
        } else {
            $this->moduleData->set('demand', $demand->forUc());
        }
        $backendUser->pushModuleData($this->moduleData->getModuleIdentifier(), $this->moduleData->toArray());

        $compareUserList = array_keys((array)$this->moduleData->get('compareUserList', []));

        $backendUsers = $this->backendUserRepository->findDemanded($demand);
        $paginator = new QueryResultPaginator($backendUsers, $currentPage, 50);
        $pagination = new SimplePagination($paginator);
        $backendUserGroups = $this->eventDispatcher->dispatch(
            new AfterBackendGroupFilterListIsAssembledEvent(
                $this->request,
                array_merge([''], $this->backendUserGroupRepository->findAll()->toArray())
            )
        )->backendGroups;

        $this->moduleTemplate->assignMultiple([
            'onlineBackendUsers' => $this->getOnlineBackendUsers(),
            'demand' => $demand,
            'paginator' => $paginator,
            'pagination' => $pagination,
            'totalAmountOfBackendUsers' => $backendUsers->count(),
            'backendUserGroups' => $backendUserGroups,
            'compareUserUidList' => array_combine($compareUserList, $compareUserList),
            'currentUserUid' => $backendUser->user['uid'] ?? null,
            'compareUserList' => !empty($compareUserList) ? $this->backendUserRepository->findByUidList($compareUserList) : '',
        ]);

        $this->addMainMenu('list');
        $createEditorButton = $this->componentFactory->createLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL))
            ->setTitle(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:btn.editor.create', 'beuser'))
            ->setShowLabelText(true)
            ->setHref((string)$this->backendUriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['be_users' => [0 => 'new']],
                'module' => 'backend_user_management',
                'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
            ]));
        $this->moduleTemplate->addButtonToButtonBar($createEditorButton);
        $createAdminButton = $this->componentFactory->createLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL))
            ->setTitle(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:btn.admin.create', 'beuser'))
            ->setShowLabelText(true)
            ->setHref((string)$this->backendUriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['be_users' => [0 => 'new']],
                'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
                'defVals' => ['be_users' => ['admin' => 1]],
            ]));
        $this->moduleTemplate->addButtonToButtonBar($createAdminButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        $shortcutButton = $this->componentFactory->createShortcutButton()
            ->setRouteIdentifier('backend_user_management')
            ->setArguments(['action' => 'list'])
            ->setDisplayName(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:backendUsers', 'beuser'));
        $this->moduleTemplate->addButtonToButtonBar($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/switch-user.js');

        return $this->moduleTemplate->renderResponse('BackendUser/List');
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

        $this->moduleTemplate->assignMultiple([
            'onlineUsersAndSessions' => $onlineUsersAndSessions,
            'currentSessionId' => $currentSessionId,
        ]);

        $this->addMainMenu('online');
        $shortcutButton = $this->componentFactory->createShortcutButton()
            ->setRouteIdentifier('backend_user_management')
            ->setArguments(['action' => 'online'])
            ->setDisplayName(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:onlineUsers', 'beuser'));
        $this->moduleTemplate->addButtonToButtonBar($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        return $this->moduleTemplate->renderResponse('BackendUser/Online');
    }

    public function showAction(int $uid = 0): ResponseInterface
    {
        $data = $this->userInformationService->getUserInformation($uid);
        $this->moduleTemplate->assignMultiple([
            'data' => $data,
            'showUid' => $this->getBackendUser()->shallDisplayDebugInformation(),
        ]);

        $this->addMainMenu('show');
        $this->moduleTemplate->addButtonToButtonBar($this->componentFactory->createBackButton((string)$this->backendUriBuilder->buildUriFromRoute('backend_user_management')));
        $editButton = $this->componentFactory->createLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-open', IconSize::SMALL))
            ->setTitle(LocalizationUtility::translate('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.edit'))
            ->setShowLabelText(true)
            ->setHref((string)$this->backendUriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['be_users' => [$uid => 'edit']],
                'module' => 'backend_user_management',
                'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
            ]));
        $this->moduleTemplate->addButtonToButtonBar($editButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        $addUserButton = $this->componentFactory->createLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL))
            ->setTitle(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:btn.backendUser.create', 'beuser'))
            ->setShowLabelText(true)
            ->setHref((string)$this->backendUriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['be_users' => [0 => 'new']],
                'module' => 'backend_user_management',
                'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
            ]));
        $this->moduleTemplate->addButtonToButtonBar($addUserButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
        $username = empty($data['user']['username']) ? '' : ': ' . $data['user']['username'];
        $shortcutButton = $this->componentFactory->createShortcutButton()
            ->setRouteIdentifier('backend_user_management')
            ->setArguments(['action' => 'show', 'uid' => $uid])
            ->setDisplayName(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:backendUser', 'beuser') . $username);
        $this->moduleTemplate->addButtonToButtonBar($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        return $this->moduleTemplate->renderResponse('BackendUser/Show');
    }

    /**
     * Compare backend users from demand
     */
    public function compareAction(): ResponseInterface
    {
        $compareUserList = array_keys((array)$this->moduleData->get('compareUserList', []));
        if (empty($compareUserList)) {
            return $this->redirect('list');
        }

        $compareData = [];
        foreach ($compareUserList as $uid) {
            if ($compareInformation = $this->userInformationService->getUserInformation($uid)) {
                $compareData[] = $compareInformation;
            }
        }

        $this->moduleTemplate->assignMultiple([
            'compareUserList' => $compareData,
            'onlineBackendUsers' => $this->getOnlineBackendUsers(),
            'showUid' => $this->getBackendUser()->shallDisplayDebugInformation(),
        ]);

        $this->addMainMenu('compare');
        $this->moduleTemplate->addButtonToButtonBar($this->componentFactory->createBackButton((string)$this->backendUriBuilder->buildUriFromRoute('backend_user_management')));
        $shortcutButton = $this->componentFactory->createShortcutButton()
            ->setRouteIdentifier('backend_user_management')
            ->setArguments(['action' => 'compare'])
            ->setDisplayName(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:compareBackendUsers', 'beuser'));
        $this->moduleTemplate->addButtonToButtonBar($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        return $this->moduleTemplate->renderResponse('BackendUser/Compare');
    }

    protected function initializeInitiatePasswordResetAction(): void
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');
    }

    /**
     * Starts the password reset process for a selected user.
     */
    public function initiatePasswordResetAction(int $user): ResponseInterface
    {
        $context = GeneralUtility::makeInstance(Context::class);
        /** @var BackendUser|null $userObject */
        $userObject = $this->backendUserRepository->findByUid($user);
        if (!$userObject || !$userObject->isPasswordResetEnabled() || !$context->getAspect('backend.user')->isAdmin()) {
            // Add an error message
            $this->addFlashMessage(
                LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:flashMessage.resetPassword.error.text', 'beuser') ?? '',
                LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:flashMessage.resetPassword.error.title', 'beuser') ?? '',
                ContextualFeedbackSeverity::ERROR
            );
        } else {
            GeneralUtility::makeInstance(PasswordReset::class)->initiateReset(
                $this->request,
                $context,
                $userObject->getEmail()
            );
            $this->addFlashMessage(
                LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:flashMessage.resetPassword.success.text', 'beuser', [$userObject->getEmail()]) ?? '',
                LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:flashMessage.resetPassword.success.title', 'beuser') ?? ''
            );
        }
        return $this->redirect('list');
    }

    protected function initializeAddToCompareListAction(): void
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');
    }

    /**
     * Attaches one backend user to the compare list
     */
    public function addToCompareListAction(int $uid): ResponseInterface
    {
        $this->addToCompareList('compareUserList', $uid);
        return $this->redirect('list');
    }

    protected function initializeRemoveFromCompareListAction(): void
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');
    }

    /**
     * Removes given backend user to the compare list
     */
    public function removeFromCompareListAction(int $uid, int $redirectToCompare = 0): ResponseInterface
    {
        $this->removeFromCompareList('compareUserList', $uid);
        if ($redirectToCompare) {
            return $this->redirect('compare');
        }
        return $this->redirect('list');
    }

    protected function initializeRemoveAllFromCompareListAction(): void
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');
    }

    /**
     * Removes all backend users from the compare list
     */
    public function removeAllFromCompareListAction(): ResponseInterface
    {
        $this->cleanCompareList('compareUserList');
        return $this->redirect('list');
    }

    protected function initializeTerminateBackendUserSessionAction(): void
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');
    }

    /**
     * Terminate BackendUser session and logout corresponding client
     * Redirects to onlineAction with message
     */
    protected function terminateBackendUserSessionAction(string $sessionId): ResponseInterface
    {
        // terminating value of persisted session ID
        $success = $this->backendUserSessionRepository->terminateSessionByIdentifier($sessionId);
        if ($success) {
            $this->addFlashMessage(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:backendUser.online.flashMessage.terminateSessionSuccess', 'beuser') ?? '');
        }
        return $this->redirect('online');
    }

    /**
     * Displays all BackendUserGroups
     */
    public function groupsAction(?BackendUserGroup $userGroupDto = null, int $currentPage = 1, string $operation = ''): ResponseInterface
    {
        $backendUser = $this->getBackendUser();

        if ($operation === 'reset-filters') {
            $this->moduleData->set('userGroupDto', []);
            $userGroupDto = null;
        }

        if ($userGroupDto === null) {
            $userGroupDto = BackendUserGroup::fromUc((array)$this->moduleData->get('userGroupDto', []));
        } else {
            $this->moduleData->set('userGroupDto', $userGroupDto->forUc());
        }

        $backendUser->pushModuleData($this->moduleData->getModuleIdentifier(), $this->moduleData->toArray());

        $backendUserGroups = $this->backendUserGroupRepository->findByFilter($userGroupDto);
        $paginator = new QueryResultPaginator($backendUserGroups, $currentPage, 50);
        $pagination = new SimplePagination($paginator);
        $compareGroupUidList = array_keys((array)$this->moduleData->get('compareGroupUidList', []));
        $this->moduleTemplate->assignMultiple(
            [
                'paginator' => $paginator,
                'pagination' => $pagination,
                'totalAmountOfBackendUserGroups' => $backendUserGroups->count(),
                'compareGroupUidList' => array_map(static function (int $value): int { // uid as key and force value to 1
                    return 1;
                }, array_flip($compareGroupUidList)),
                'compareGroupList' => !empty($compareGroupUidList) ? $this->backendUserGroupRepository->findByUidList($compareGroupUidList) : [],
                'userGroupDto' => $userGroupDto,
            ]
        );

        $this->addMainMenu('groups');
        $addGroupButton = $this->componentFactory->createLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL))
            ->setTitle(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:btn.backendUserGroup.create', 'beuser'))
            ->setShowLabelText(true)
            ->setHref((string)$this->backendUriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['be_groups' => [0 => 'new']],
                'module' => 'backend_user_management',
                'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
            ]));
        $this->moduleTemplate->addButtonToButtonBar($addGroupButton);
        $shortcutButton = $this->componentFactory->createShortcutButton()
            ->setRouteIdentifier('backend_user_management')
            ->setArguments(['action' => 'groups'])
            ->setDisplayName(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:backendUserGroupsMenu', 'beuser'));
        $this->moduleTemplate->addButtonToButtonBar($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        return $this->moduleTemplate->renderResponse('BackendUserGroup/List');
    }

    /**
     * Show a single backend user group.
     */
    public function showGroupAction(int $uid = 0): ResponseInterface
    {
        $data = $this->userInformationService->getGroupInformation($uid);
        $this->moduleTemplate->assignMultiple([
            'data' => $data,
            'showUid' => $this->getBackendUser()->shallDisplayDebugInformation(),
        ]);

        $this->addMainMenu('showGroup');
        $this->moduleTemplate->addButtonToButtonBar($this->componentFactory->createBackButton((string)$this->backendUriBuilder->buildUriFromRoute('backend_user_management', ['action' => 'groups'])));
        $editButton = $this->componentFactory->createLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-open', IconSize::SMALL))
            ->setTitle(
                LocalizationUtility::translate(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.edit'
                )
            )
            ->setShowLabelText(true)
            ->setHref((string)$this->backendUriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['be_groups' => [$uid => 'edit']],
                'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
            ]));
        $this->moduleTemplate->addButtonToButtonBar($editButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        $addUserButton = $this->componentFactory->createLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL))
            ->setTitle(
                LocalizationUtility::translate(
                    'LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:btn.backendUserGroup.create',
                    'beuser'
                )
            )
            ->setShowLabelText(true)
            ->setHref((string)$this->backendUriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['be_groups' => [0 => 'new']],
                'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
            ]));
        $this->moduleTemplate->addButtonToButtonBar($addUserButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
        $backendGroupTitle = empty($data['group']['title']) ? '' : ': ' . $data['group']['title'];
        $shortcutButton = $this->componentFactory->createShortcutButton()
            ->setRouteIdentifier('backend_user_management')
            ->setArguments(['action' => 'showGroup', 'uid' => $uid])
            ->setDisplayName(
                LocalizationUtility::translate(
                    'LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:backendUserGroup',
                    'beuser'
                ) . $backendGroupTitle
            );
        $this->moduleTemplate->addButtonToButtonBar($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        return $this->moduleTemplate->renderResponse('BackendUserGroup/Show');
    }

    public function compareGroupsAction(): ResponseInterface
    {
        $compareGroupUidList = array_keys((array)$this->moduleData->get('compareGroupUidList', []));

        $compareData = [];
        foreach ($compareGroupUidList as $uid) {
            if ($compareInformation = $this->userInformationService->getGroupInformation($uid)) {
                $compareData[] = $compareInformation;
            }
        }
        if (empty($compareData)) {
            return $this->redirect('groups');
        }

        $this->moduleTemplate->assignMultiple([
            'compareGroupList' => $compareData,
            'showUid' => $this->getBackendUser()->shallDisplayDebugInformation(),
        ]);

        $this->addMainMenu('compareGroups');
        $this->moduleTemplate->addButtonToButtonBar($this->componentFactory->createBackButton($this->uriBuilder->uriFor('groups')));
        $shortcutButton = $this->componentFactory->createShortcutButton()
            ->setRouteIdentifier('backend_user_management')
            ->setArguments(['action' => 'compareGroups'])
            ->setDisplayName(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:compareBackendUsersGroups', 'beuser'));
        $this->moduleTemplate->addButtonToButtonBar($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        return $this->moduleTemplate->renderResponse('BackendUserGroup/Compare');
    }

    protected function initializeAddGroupToCompareListAction(): void
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');
    }

    /**
     * Attaches one backend user group to the compare list
     */
    public function addGroupToCompareListAction(int $uid): ResponseInterface
    {
        $this->addToCompareList('compareGroupUidList', $uid);
        return $this->redirect('groups');
    }

    protected function initializeRemoveGroupFromCompareListAction(): void
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');
    }

    /**
     * Removes given backend user group to the compare list
     */
    public function removeGroupFromCompareListAction(int $uid, int $redirectToCompare = 0): ResponseInterface
    {
        $this->removeFromCompareList('compareGroupUidList', $uid);
        if ($redirectToCompare) {
            return $this->redirect('compareGroups');
        }
        return $this->redirect('groups');
    }

    protected function initializeRemoveAllGroupsFromCompareListAction(): void
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');
    }

    /**
     * Removes all backend user groups from the compare list
     */
    public function removeAllGroupsFromCompareListAction(): ResponseInterface
    {
        $this->cleanCompareList('compareGroupUidList');
        return $this->redirect('groups');
    }

    protected function filemountsAction(int $currentPage = 1): ResponseInterface
    {
        $filemounts = $this->eventDispatcher->dispatch(
            new AfterFilemountsListIsAssembledEvent(
                $this->request,
                $this->fileMountRepository->findAll()->toArray()
            )
        )->filemounts;

        $this->addMainMenu('filemounts');

        $paginator = new ArrayPaginator($filemounts, $currentPage, 50);
        $pagination = new SimplePagination($paginator);
        $this->moduleTemplate->assignMultiple(
            [
                'paginator' => $paginator,
                'pagination' => $pagination,
                'totalAmountOfFilemounts' => count($filemounts),
            ]
        );

        $addFilemountButton = $this->componentFactory->createLinkButton()
            ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL))
            ->setTitle(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:filemount.create', 'beuser'))
            ->setShowLabelText(true)
            ->setHref((string)$this->backendUriBuilder->buildUriFromRoute('record_edit', [
                'edit' => ['sys_filemounts' => [0 => 'new']],
                'module' => 'backend_user_management',
                'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
            ]));
        $this->moduleTemplate->addButtonToButtonBar($addFilemountButton);

        return $this->moduleTemplate->renderResponse('Filemount/List');
    }

    /**
     * Create an array with the uids of online users as the keys
     * [
     *   1 => true,
     *   5 => true
     * ]
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
     */
    protected function addMainMenu(string $currentAction): void
    {
        $this->uriBuilder->setRequest($this->request);
        $menu = $this->componentFactory->createMenu();
        $menu->setIdentifier('BackendUserModuleMenu');
        $menu->setLabel(
            LocalizationUtility::translate(
                'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:modulemenu.label',
                'backend',
            )
        );
        $menu->addMenuItem(
            $this->componentFactory->createMenuItem()
                ->setTitle(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:backendUsers', 'beuser'))
                ->setHref($this->uriBuilder->uriFor('list'))
                ->setActive($currentAction === 'list')
        );
        if ($currentAction === 'show') {
            $menu->addMenuItem(
                $this->componentFactory->createMenuItem()
                    ->setTitle(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:backendUserDetails', 'beuser'))
                    ->setHref($this->uriBuilder->uriFor('show'))
                    ->setActive(true)
            );
        }
        if ($currentAction === 'compare') {
            $menu->addMenuItem(
                $this->componentFactory->createMenuItem()
                    ->setTitle(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:compareBackendUsers', 'beuser'))
                    ->setHref($this->uriBuilder->uriFor('list'))
                    ->setActive(true)
            );
        }
        $menu->addMenuItem(
            $this->componentFactory->createMenuItem()
                ->setTitle(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:backendUserGroupsMenu', 'beuser'))
                ->setHref($this->uriBuilder->uriFor('groups'))
                ->setActive($currentAction === 'groups')
        );
        if ($currentAction === 'showGroup') {
            $menu->addMenuItem(
                $this->componentFactory->createMenuItem()
                    ->setTitle(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:backendUserGroupDetails', 'beuser'))
                    ->setHref($this->uriBuilder->uriFor('showGroup'))
                    ->setActive(true)
            );
        }
        if ($currentAction === 'compareGroups') {
            $menu->addMenuItem(
                $this->componentFactory->createMenuItem()
                    ->setTitle(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:compareBackendUsersGroups', 'beuser'))
                    ->setHref($this->uriBuilder->uriFor('compareGroups'))
                    ->setActive(true)
            );
        }
        $menu->addMenuItem(
            $this->componentFactory->createMenuItem()
                ->setTitle(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:onlineUsers', 'beuser'))
                ->setHref($this->uriBuilder->uriFor('online'))
                ->setActive($currentAction === 'online')
        );
        $menu->addMenuItem(
            $this->componentFactory->createMenuItem()
                ->setTitle(LocalizationUtility::translate('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:filemounts', 'beuser'))
                ->setHref($this->uriBuilder->uriFor('filemounts'))
                ->setActive($currentAction === 'filemounts')
        );
        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Attaches the given uid to the requested compare list
     */
    protected function addToCompareList(string $listIdentifier, int $uid): void
    {
        $compareList = (array)$this->moduleData->get($listIdentifier, []);
        $compareList[$uid] = true;
        $this->moduleData->set($listIdentifier, $compareList);
        $this->getBackendUser()->pushModuleData($this->moduleData->getModuleIdentifier(), $this->moduleData->toArray());
    }

    /**
     * Removes the given uid from the requested compare list
     */
    protected function removeFromCompareList(string $listIdentifier, int $uid): void
    {
        $compareList = (array)$this->moduleData->get($listIdentifier, []);
        unset($compareList[$uid]);
        $this->moduleData->set($listIdentifier, $compareList);
        $this->getBackendUser()->pushModuleData($this->moduleData->getModuleIdentifier(), $this->moduleData->toArray());
    }

    /**
     * Removes all items from the requested compare list
     */
    protected function cleanCompareList(string $listIdentifier): void
    {
        $this->moduleData->set($listIdentifier, []);
        $this->getBackendUser()->pushModuleData($this->moduleData->getModuleIdentifier(), $this->moduleData->toArray());
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
