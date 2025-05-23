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
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownHeader;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownRadio;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend module page permissions. This is the "Access" module in main module.
 * Also includes the ajax endpoint for convenient methods for editing
 * of page permissions (including page ownership (user and group)).
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
class PermissionController
{
    private const SESSION_PREFIX = 'tx_Beuser_';
    private const DEPTH_LEVELS = [1, 2, 3, 4, 10];
    private const RECURSIVE_LEVELS = 10;

    protected int $id = 0;
    protected string $returnUrl = '';
    protected int $depth;
    protected array $pageInfo = [];

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ResponseFactoryInterface $responseFactory,
        protected readonly BackendViewFactory $backendViewFactory,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $backendUser = $this->getBackendUser();
        $lang = $this->getLanguageService();

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

        if ($action === 'update') {
            // Update returns a redirect. No further fiddling with view here, return directly.
            return $this->updateAction($request);
        }

        $view = $this->moduleTemplateFactory->create($request);
        if ($backendUser->workspace !== 0) {
            $this->addFlashMessage(
                $lang->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:WorkspaceWarningText'),
                $lang->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:WorkspaceWarning'),
                ContextualFeedbackSeverity::WARNING
            );
        }
        $this->registerDocHeaderButtons($view, $action);
        $view->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:mlang_tabs_tab'),
            $this->id !== 0 && !empty($this->pageInfo['title']) ? $this->pageInfo['title'] : ''
        );
        $view->getDocHeaderComponent()->setMetaInformation($this->pageInfo);

        if ($action === 'edit') {
            return $this->editAction($view, $request);
        }
        return $this->indexAction($view, $request);
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
        $view = $this->backendViewFactory->create($request);
        $view->assign('pageId', $conf['page']);

        // Initialize TCE for execution of updates
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        // Determine the action to execute
        switch ($conf['action'] ?? '') {
            case 'show_change_owner_selector':
                $template = 'Permission/ChangeOwnerSelector';
                $users = BackendUtility::getUserNames();
                $view->assignMultiple([
                    'elementId' => 'o_' . $conf['page'],
                    'ownerUid' => $conf['ownerUid'],
                    'username' => $conf['username'],
                    'users' => $users,
                    'addCurrentUser' => !isset($users[$conf['ownerUid']]),
                ]);
                break;
            case 'show_change_group_selector':
                $template = 'Permission/ChangeGroupSelector';
                $groups = BackendUtility::getGroupNames();
                $view->assignMultiple([
                    'elementId' => 'g_' . $conf['page'],
                    'groupUid' => $conf['groupUid'],
                    'groupname' => $conf['groupname'],
                    'groups' => $groups,
                    'addCurrentGroup' => !isset($groups[$conf['groupUid']]),
                ]);
                break;
            case 'toggle_edit_lock':
                // Initialize requested lock state
                $editLockState = $conf['editLockState'] ? 0 : 1;

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
                $template = 'Permission/ToggleEditLock';
                $view->assignMultiple([
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
                $template = 'Permission/ChangeOwner';
                $view->assignMultiple([
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
                $template = 'Permission/ChangeGroup';
                $view->assignMultiple([
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
                $template = 'Permission/ChangePermission';
                $view->assignMultiple([
                    'permission' => $conf['permissions'],
                    'scope' => $conf['who'],
                ]);
        }

        return $this->htmlResponse($view->render($template));
    }

    public function indexAction(ModuleTemplate $view, ServerRequestInterface $request): ResponseInterface
    {
        $view->assignMultiple([
            'currentId' => $this->id,
            'viewTree' => $this->getTree(),
            'beUsers' => BackendUtility::getUserNames(),
            'beGroups' => BackendUtility::getGroupNames(),
            'depth' => $this->depth,
            'depthBaseUrl' => $this->uriBuilder->buildUriFromRoute('permissions_pages', [
                'id' => $this->id,
                'depth' => '${value}',
                'action' => 'index',
            ]),
            'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('permissions_pages', [
                'id' => $this->id,
                'depth' => $this->depth,
                'action' => 'index',
            ]),
        ]);

        return $view->renderResponse('Permission/Index');
    }

    public function editAction(ModuleTemplate $view, ServerRequestInterface $request): ResponseInterface
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

        $view->assignMultiple([
            'id' => $this->id,
            'depth' => $this->depth,
            'currentBeUser' => $this->pageInfo['perms_userid'] ?? 0,
            'beUserData' => $beUserDataArray,
            'currentBeGroup' => $this->pageInfo['perms_groupid'] ?? 0,
            'beGroupData' => $beGroupDataArray,
            'pageInfo' => $this->pageInfo,
            'returnUrl' => $this->returnUrl,
            'recursiveSelectOptions' => $this->getRecursiveSelectOptions(),
            'formAction' => (string)$this->uriBuilder->buildUriFromRoute('permissions_pages', [
                'action' => 'update',
                'id' => $this->id,
                'depth' => $this->depth,
                'returnUrl' => $this->returnUrl,
            ]),
        ]);

        return $view->renderResponse('Permission/Edit');
    }

    protected function updateAction(ServerRequestInterface $request): ResponseInterface
    {
        $data = (array)($request->getParsedBody()['data'] ?? []);
        $mirror = (array)($request->getParsedBody()['mirror'] ?? []);

        $dataHandlerInput = [];
        // Prepare the input data for data handler
        $dataPages = $data['pages'] ?? null;
        if (is_array($dataPages) && $dataPages !== []) {
            foreach ($dataPages as $pageUid => $properties) {
                // if the owner and group field shouldn't be touched, unset the option
                if ((int)($properties['perms_userid'] ?? 0) === -1) {
                    unset($properties['perms_userid']);
                }
                if ((int)($properties['perms_groupid'] ?? 0) === -1) {
                    unset($properties['perms_groupid']);
                }
                $dataHandlerInput[$pageUid] = $properties;
                if (!empty($mirror['pages'][$pageUid])) {
                    $mirrorPages = GeneralUtility::intExplode(',', (string)$mirror['pages'][$pageUid]);
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

    protected function registerDocHeaderButtons(ModuleTemplate $view, string $action): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $lang = $this->getLanguageService();

        if ($action === 'edit') {
            // CLOSE button:
            if ($this->returnUrl !== '') {
                $closeButton = $buttonBar->makeLinkButton()
                    ->setHref($this->returnUrl)
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
                    ->setShowLabelText(true)
                    ->setIcon($this->iconFactory->getIcon('actions-close', IconSize::SMALL));
                $buttonBar->addButton($closeButton);
            }

            // SAVE button:
            $saveButton = $buttonBar->makeInputButton()
                ->setName('_save')
                ->setValue('1')
                ->setForm('PermissionControllerEdit')
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveCloseDoc'))
                ->setShowLabelText(true)
                ->setIcon($this->iconFactory->getIcon('actions-document-save', IconSize::SMALL));
            $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        }

        if ($action === 'index' && count($this->getDepthOptions()) > 0) {
            $viewModeItems = [];
            $viewModeItems[] = GeneralUtility::makeInstance(DropDownHeader::class)
                ->setLabel($lang->sL('LLL:EXT:beuser/Resources/Private/Language/locallang_mod_permission.xlf:Depth'));
            foreach ($this->getDepthOptions() as $value => $label) {
                $viewModeItems[] = GeneralUtility::makeInstance(DropDownRadio::class)
                    ->setActive($this->depth === $value)
                    ->setLabel($label)
                    ->setHref((string)$this->uriBuilder->buildUriFromRoute('permissions_pages', [
                        'id' => $this->id,
                        'depth' => $value,
                    ]));
            }
            $viewModeButton = $buttonBar->makeDropDownButton()
                ->setLabel($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view'))
                ->setShowLabelText(true);
            foreach ($viewModeItems as $viewModeItem) {
                $viewModeButton->addItem($viewModeItem);
            }
            $buttonBar->addButton($viewModeButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);
        }

        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('permissions_pages')
            ->setDisplayName($this->getShortcutTitle())
            ->setArguments(['id' => $this->id, 'action' => $action]);
        $buttonBar->addButton($shortcutButton);
    }

    protected function getTree(): array
    {
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init();

        // Create the tree from $this->id
        if ($this->id) {
            $icon = $this->iconFactory->getIconForRecord('pages', $this->pageInfo, IconSize::SMALL);
        } else {
            $icon = $this->iconFactory->getIcon('apps-pagetree-root', IconSize::SMALL);
        }
        $iconMarkup = '<span title="' . BackendUtility::getRecordIconAltText($this->pageInfo, 'pages') . '">' . $icon->render() . '</span>';
        $tree->tree[] = ['row' => $this->pageInfo, 'HTML' => '', 'icon' => $iconMarkup];
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
     */
    protected function getRecursiveSelectOptions(): array
    {
        $lang = $this->getLanguageService();
        // Initialize tree object:
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init();
        $tree->makeHTML = false;
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
     */
    protected function addFlashMessage(string $message, string $title = '', ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::INFO): void
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $title, $severity, true);
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Returns the shortcut title for the current page
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
