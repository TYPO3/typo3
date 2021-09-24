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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Backend module page permissions.
 * Also includes the ajax endpoint for convenient methods for editing
 * of page permissions (including page ownership (user and group)).
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class PermissionController
{
    private const SESSION_PREFIX = 'tx_Beuser_';
    private const ALLOWED_ACTIONS = ['index', 'edit', 'update'];
    private const DEPTH_LEVELS = [1, 2, 3, 4, 10];
    private const RECURSIVE_LEVELS = 10;

    protected int $id = 0;
    protected string $returnUrl = '';
    protected int $depth;
    protected array $pageInfo = [];

    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected PageRenderer $pageRenderer;
    protected IconFactory $iconFactory;
    protected UriBuilder $uriBuilder;
    protected ResponseFactoryInterface $responseFactory;

    protected ?ModuleTemplate $moduleTemplate = null;
    protected ?ViewInterface $view = null;

    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        PageRenderer $pageRenderer,
        IconFactory $iconFactory,
        UriBuilder $uriBuilder,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->pageRenderer = $pageRenderer;
        $this->iconFactory = $iconFactory;
        $this->uriBuilder = $uriBuilder;
        $this->responseFactory = $responseFactory;
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $backendUser = $this->getBackendUser();
        $lang = $this->getLanguageService();

        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);

        // determine depth parameter
        $this->depth = (int)($parsedBody['depth'] ?? $queryParams['depth'] ?? 0);
        if (!$this->depth) {
            $this->depth = (int)$backendUser->getSessionData(self::SESSION_PREFIX . 'depth');
        } else {
            $backendUser->setAndSaveSessionData(self::SESSION_PREFIX . 'depth', $this->depth);
        }

        // determine id parameter
        $this->id = (int)($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        $pageRecord = BackendUtility::getRecord('pages', $this->id);
        // Check if a page with the given id exists, otherwise fall back
        if ($pageRecord === null) {
            $this->id = 0;
        }

        $this->returnUrl = GeneralUtility::sanitizeLocalUrl((string)($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? ''));
        $this->pageInfo = BackendUtility::readPageAccess($this->id, ' 1=1') ?: [
            'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
            'uid' => 0,
            'pid' => 0,
        ];

        $action = (string)($parsedBody['action'] ?? $queryParams['action'] ?? 'index');
        if (!in_array($action, self::ALLOWED_ACTIONS, true)) {
            return $this->htmlResponse('Action not allowed', 400);
        }

        if ($action !== 'update') {
            $template = ucfirst($action);
            if ($backendUser->workspace !== 0) {
                $this->addFlashMessage(
                    $lang->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:WorkspaceWarningText'),
                    $lang->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:WorkspaceWarning'),
                    FlashMessage::WARNING
                );
            }
            $this->initializeView($template);
            $this->registerDocHeaderButtons($action);
            $this->moduleTemplate->setTitle(
                $this->getLanguageService()->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:mlang_tabs_tab'),
                $this->id !== 0 && !empty($this->pageInfo['title']) ? $this->pageInfo['title'] : ''
            );
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageInfo);
        }

        return $this->{$action . 'Action'}($request);
    }

    public function handleAjaxRequest(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $conf = [
            'page' => $parsedBody['page'] ?? null,
            'who' => $parsedBody['who'] ?? null,
            'mode' => $parsedBody['mode'] ?? null,
            'bits' => (int)($parsedBody['bits'] ?? 0),
            'permissions' => (int)($parsedBody['permissions'] ?? 0),
            'action' => $parsedBody['action'] ?? null,
            'ownerUid' => (int)($parsedBody['ownerUid'] ?? 0),
            'username' => $parsedBody['username'] ?? null,
            'groupUid' => (int)($parsedBody['groupUid'] ?? 0),
            'groupname' => $parsedBody['groupname'] ?? '',
            'editLockState' => (int)($parsedBody['editLockState'] ?? 0),
            'new_owner_uid' => (int)($parsedBody['newOwnerUid'] ?? 0),
            'new_group_uid' => (int)($parsedBody['newGroupUid'] ?? 0),
        ];

        // Basic test for required value
        if ($conf['page'] <= 0) {
            return $this->htmlResponse('This script cannot be called directly', 500);
        }

        // Initialize view and always assign current page id
        $this->initializeView();
        $this->view->assign('pageId', $conf['page']);

        // Initialize TCE for execution of updates
        $tce = GeneralUtility::makeInstance(DataHandler::class);

        // Determine the action to execute
        switch ($conf['action'] ?? '') {
            case 'show_change_owner_selector':
                $this->view->setTemplatePathAndFilename(
                    GeneralUtility::getFileAbsFileName('EXT:beuser/Resources/Private/Templates/Permission/ChangeOwnerSelector.html')
                );
                $users = BackendUtility::getUserNames();
                $this->view->assignMultiple([
                    'elementId' => 'o_' . $conf['page'],
                    'ownerUid' => $conf['ownerUid'],
                    'username' => $conf['username'],
                    'users' => $users,
                    'addCurrentUser' => !isset($users[$conf['ownerUid']]),
                ]);
                break;
            case 'show_change_group_selector':
                $this->view->setTemplatePathAndFilename(
                    GeneralUtility::getFileAbsFileName('EXT:beuser/Resources/Private/Templates/Permission/ChangeGroupSelector.html')
                );
                $groups = BackendUtility::getGroupNames();
                $this->view->assignMultiple([
                    'elementId' => 'g_' . $conf['page'],
                    'groupUid' => $conf['groupUid'],
                    'groupname' => $conf['groupname'],
                    'groups' => $groups,
                    'addCurrentGroup' => !isset($groups[$conf['groupUid']]),
                ]);
                break;
            case 'toggle_edit_lock':
                // Initialize requested lock state
                $editLockState = !$conf['editLockState'];

                // Execute TCE Update
                $tce->start([
                    'pages' => [
                        $conf['page'] => [
                            'editlock' => $editLockState,
                        ],
                    ],
                ], []);
                $tce->process_datamap();

                // Setup view
                $this->view->setTemplatePathAndFilename(
                    GeneralUtility::getFileAbsFileName('EXT:beuser/Resources/Private/Templates/Permission/ToggleEditLock.html')
                );
                $this->view->assignMultiple([
                    'elementId' => 'el_' . $conf['page'],
                    'editLockState' => $editLockState,
                ]);
                break;
            case 'change_owner':
                // Check if new owner uid is given (also accept 0 => [not set])
                if ($conf['new_owner_uid'] < 0) {
                    return $this->htmlResponse('An error occurred: No page owner uid specified', 500);
                }

                // Execute TCE Update
                $tce->start([
                    'pages' => [
                        $conf['page'] => [
                            'perms_userid' => $conf['new_owner_uid'],
                        ],
                    ],
                ], []);
                $tce->process_datamap();

                // Setup and render view
                $this->view->setTemplatePathAndFilename(
                    GeneralUtility::getFileAbsFileName('EXT:beuser/Resources/Private/Templates/Permission/ChangeOwner.html')
                );
                $this->view->assignMultiple([
                    'userId' => $conf['new_owner_uid'],
                    'username' => BackendUtility::getUserNames(
                        'username',
                        ' AND uid = ' . $conf['new_owner_uid']
                    )[$conf['new_owner_uid']]['username'] ?? '',
                ]);
                break;
            case 'change_group':
                // Check if new group uid is given (also accept 0 => [not set])
                if ($conf['new_group_uid'] < 0) {
                    return $this->htmlResponse('An error occurred: No page group uid specified', 500);
                }

                // Execute TCE Update
                $tce->start([
                    'pages' => [
                        $conf['page'] => [
                            'perms_groupid' => $conf['new_group_uid'],
                        ],
                    ],
                ], []);
                $tce->process_datamap();

                // Setup and render view
                $this->view->setTemplatePathAndFilename(
                    GeneralUtility::getFileAbsFileName('EXT:beuser/Resources/Private/Templates/Permission/ChangeGroup.html')
                );
                $this->view->assignMultiple([
                    'groupId' => $conf['new_group_uid'],
                    'groupname' => BackendUtility::getGroupNames(
                        'title',
                        ' AND uid = ' . $conf['new_group_uid']
                    )[$conf['new_group_uid']]['title'] ?? '',
                ]);
                break;
            default:
                // Initialize permissions state
                if ($conf['mode'] === 'delete') {
                    $conf['permissions'] -= $conf['bits'];
                } else {
                    $conf['permissions'] += $conf['bits'];
                }

                // Execute TCE Update
                $tce->start([
                    'pages' => [
                        $conf['page'] => [
                            'perms_' . $conf['who'] => $conf['permissions'],
                        ],
                    ],
                ], []);
                $tce->process_datamap();

                // Setup and render view
                $this->view->setTemplatePathAndFilename(
                    GeneralUtility::getFileAbsFileName('EXT:beuser/Resources/Private/Templates/Permission/ChangePermission.html')
                );
                $this->view->assignMultiple([
                    'permission' => $conf['permissions'],
                    'scope' => $conf['who'],
                ]);
        }

        return $this->htmlResponse($this->view->render());
    }

    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->view->assignMultiple([
            'currentId' => $this->id,
            'viewTree' => $this->getTree(),
            'beUsers' => BackendUtility::getUserNames(),
            'beGroups' => BackendUtility::getGroupNames(),
            'depth' => $this->depth,
            'depthOptions' => $this->getDepthOptions(),
            'depthBaseUrl' => $this->uriBuilder->buildUriFromRoute('system_BeuserTxPermission', [
                'id' => $this->id,
                'depth' => '${value}',
                'action' => 'index',
            ]),
            'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('system_BeuserTxPermission', [
                'id' => $this->id,
                'depth' => $this->depth,
                'action' => 'index',
            ]),
        ]);

        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    public function editAction(ServerRequestInterface $request): ResponseInterface
    {
        $lang = $this->getLanguageService();
        $selectNone = $lang->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:selectNone');
        $selectUnchanged = $lang->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:selectUnchanged');

        // Owner selector
        $beUserDataArray = [0 => $selectNone];
        foreach (BackendUtility::getUserNames() as $uid => $row) {
            $beUserDataArray[$uid] = $row['username'] ?? '';
        }
        $beUserDataArray[-1] = $selectUnchanged;

        // Group selector
        $beGroupDataArray = [0 => $selectNone];
        foreach (BackendUtility::getGroupNames() as $uid => $row) {
            $beGroupDataArray[$uid] = $row['title'] ?? '';
        }
        $beGroupDataArray[-1] = $selectUnchanged;

        $this->view->assignMultiple([
            'id' => $this->id,
            'depth' => $this->depth,
            'currentBeUser' => $this->pageInfo['perms_userid'] ?? 0,
            'beUserData' => $beUserDataArray,
            'currentBeGroup' => $this->pageInfo['perms_groupid'] ?? 0,
            'beGroupData' => $beGroupDataArray,
            'pageInfo' => $this->pageInfo,
            'returnUrl' => $this->returnUrl,
            'recursiveSelectOptions' => $this->getRecursiveSelectOptions(),
            'formAction' => (string)$this->uriBuilder->buildUriFromRoute('system_BeuserTxPermission', [
                'action' => 'update',
                'id' => $this->id,
                'depth' => $this->depth,
                'returnUrl' => $this->returnUrl,
            ]),
        ]);

        return $this->htmlResponse($this->moduleTemplate->setContent($this->view->render())->renderContent());
    }

    protected function updateAction(ServerRequestInterface $request): ResponseInterface
    {
        $data = (array)($request->getParsedBody()['data'] ?? []);
        $mirror = (array)($request->getParsedBody()['mirror'] ?? []);

        $dataHandlerInput = [];
        // Prepare the input data for data handler
        if (is_array($data['pages'] ?? false) && $data['pages'] !== []) {
            foreach ($data['pages'] as $pageUid => $properties) {
                // if the owner and group field shouldn't be touched, unset the option
                if ((int)$properties['perms_userid'] === -1) {
                    unset($properties['perms_userid']);
                }
                if ((int)$properties['perms_groupid'] === -1) {
                    unset($properties['perms_groupid']);
                }
                $dataHandlerInput[$pageUid] = $properties;
                if (!empty($mirror['pages'][$pageUid])) {
                    $mirrorPages = GeneralUtility::intExplode(',', $mirror['pages'][$pageUid]);
                    foreach ($mirrorPages as $mirrorPageUid) {
                        $dataHandlerInput[$mirrorPageUid] = $properties;
                    }
                }
            }
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'pages' => $dataHandlerInput,
            ],
            []
        );
        $dataHandler->process_datamap();

        return $this->responseFactory->createResponse(303)
            ->withHeader('location', $this->returnUrl);
    }

    protected function initializeView(string $template = ''): void
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplateRootPaths(['EXT:beuser/Resources/Private/Templates/Permission']);
        $this->view->setPartialRootPaths(['EXT:beuser/Resources/Private/Partials/Permission']);
        $this->view->setLayoutRootPaths(['EXT:beuser/Resources/Private/Layouts']);
        if ($template !== '') {
            $this->view->setTemplatePathAndFilename(
                GeneralUtility::getFileAbsFileName('EXT:beuser/Resources/Private/Templates/Permission/' . $template . '.html')
            );
            // Only add JS modules in case a template is given, as otherwise this may be a ajax request
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Beuser/Permissions');
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
        }
    }

    protected function registerDocHeaderButtons(string $action): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $lang = $this->getLanguageService();

        if ($action === 'edit') {
            // CLOSE button:
            if ($this->returnUrl !== '') {
                $closeButton = $buttonBar->makeLinkButton()
                    ->setHref($this->returnUrl)
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
                    ->setShowLabelText(true)
                    ->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL));
                $buttonBar->addButton($closeButton);
            }

            // SAVE button:
            $saveButton = $buttonBar->makeInputButton()
                ->setName('_save')
                ->setValue('1')
                ->setForm('PermissionControllerEdit')
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveCloseDoc'))
                ->setShowLabelText(true)
                ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL));
            $buttonBar->addButton($saveButton);
        }

        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('system_BeuserTxPermission')
            ->setDisplayName($this->getShortcutTitle())
            ->setArguments(['id' => $this->id, 'action' => $action]);
        $buttonBar->addButton($shortcutButton);

        $helpButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName('perm_module');

        $buttonBar->addButton($helpButton);
    }

    protected function getTree(): array
    {
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init();
        $tree->addField('perms_user', true);
        $tree->addField('perms_group', true);
        $tree->addField('perms_everybody', true);
        $tree->addField('perms_userid', true);
        $tree->addField('perms_groupid', true);
        $tree->addField('hidden');
        $tree->addField('fe_group');
        $tree->addField('starttime');
        $tree->addField('endtime');
        $tree->addField('editlock');

        // Create the tree from $this->id
        if ($this->id) {
            $tree->tree[] = ['row' => $this->pageInfo, 'HTML' => $tree->getIcon($this->pageInfo)];
        } else {
            $tree->tree[] = ['row' => $this->pageInfo, 'HTML' => $tree->getRootIcon($this->pageInfo)];
        }
        $tree->getTree($this->id, $this->depth);

        return $tree->tree;
    }

    protected function getDepthOptions(): array
    {
        $depthOptions = [];
        foreach (self::DEPTH_LEVELS as $depthLevel) {
            $levelLabel = $depthLevel === 1 ? 'level' : 'levels';
            $depthOptions[$depthLevel] = $depthLevel . ' ' . $this->getLanguageService()->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:' . $levelLabel);
        }
        return $depthOptions;
    }

    /**
     * Finding tree and offer setting of values recursively.
     *
     * @return array
     */
    protected function getRecursiveSelectOptions(): array
    {
        $lang = $this->getLanguageService();
        // Initialize tree object:
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init();
        $tree->addField('perms_userid', true);
        $tree->makeHTML = 0;
        // Make tree:
        $tree->getTree($this->id, self::RECURSIVE_LEVELS);
        $options = [
            '' => $lang->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:selectNone'),
        ];
        // If there are a hierarchy of page ids, then...
        if (!empty($tree->orig_ids_hierarchy) && ($this->getBackendUser()->user['uid'] ?? false)) {
            // Init:
            $labelRecursive = $lang->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:recursive');
            $labelLevel = $lang->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:level');
            $labelLevels = $lang->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:levels');
            $labelPageAffected = $lang->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:page_affected');
            $labelPagesAffected = $lang->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:pages_affected');
            $theIdListArr = [];
            // Traverse the number of levels we want to allow recursive
            // setting of permissions for:
            for ($a = self::RECURSIVE_LEVELS; $a > 0; $a--) {
                if (is_array($tree->orig_ids_hierarchy[$a] ?? false)) {
                    foreach ($tree->orig_ids_hierarchy[$a] as $theId) {
                        $theIdListArr[] = $theId;
                    }
                    $lKey = self::RECURSIVE_LEVELS - $a + 1;
                    $pagesCount = count($theIdListArr);
                    $options[implode(',', $theIdListArr)] = $labelRecursive . ' ' . $lKey . ' ' . ($lKey === 1 ? $labelLevel : $labelLevels) .
                        ' (' . $pagesCount . ' ' . ($pagesCount === 1 ? $labelPageAffected : $labelPagesAffected) . ')';
                }
            }
        }
        return $options;
    }

    /**
     * Adds a flash message to the default flash message queue
     *
     * @param string $message
     * @param string $title
     * @param int $severity
     */
    protected function addFlashMessage(string $message, string $title = '', int $severity = FlashMessage::INFO): void
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $title, $severity, true);
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Returns the shortcut title for the current page
     *
     * @return string
     */
    protected function getShortcutTitle(): string
    {
        return sprintf(
            '%s: %s [%d]',
            $this->getLanguageService()->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            BackendUtility::getRecordTitle('pages', $this->pageInfo),
            $this->id
        );
    }

    protected function htmlResponse(string $html, int $code = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($code)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write($html);
        return $response;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
