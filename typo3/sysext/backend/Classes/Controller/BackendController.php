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

namespace TYPO3\CMS\Backend\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent;
use TYPO3\CMS\Backend\Module\MenuModule;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\PageRendererBackendSetupTrait;
use TYPO3\CMS\Backend\Toolbar\RequestAwareToolbarItemInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemsRegistry;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\View\ViewInterface;

/**
 * Class for rendering the TYPO3 backend.
 * This is the backend outer main frame with topbar and module menu.
 */
#[Controller]
class BackendController
{
    use PageRendererBackendSetupTrait;

    /**
     * @var ModuleInterface[]
     */
    protected array $modules;

    public function __construct(
        protected readonly Typo3Version $typo3Version,
        protected readonly UriBuilder $uriBuilder,
        protected readonly PageRenderer $pageRenderer,
        protected readonly ModuleProvider $moduleProvider,
        protected readonly ToolbarItemsRegistry $toolbarItemsRegistry,
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly BackendViewFactory $viewFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly FlashMessageService $flashMessageService,
    ) {
        $this->modules = $this->moduleProvider->getModulesForModuleMenu($this->getBackendUser());
    }

    /**
     * Main function generating the BE scaffolding.
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $pageRenderer = $this->pageRenderer;
        // apply nonce hint for elements that are shown in a modal
        $pageRenderer->setApplyNonceHint(true);

        $this->setUpBasicPageRendererForBackend($pageRenderer, $this->extensionConfiguration, $request, $this->getLanguageService());

        $javaScriptRenderer = $pageRenderer->getJavaScriptRenderer();
        $javaScriptRenderer->addGlobalAssignment(['window' => [
            'name' => 'typo3-backend', // reset window name to a standardized value
            'opener' => null, // remove any previously set opener value
        ]]);
        $javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/backend/login-refresh.js')
                ->invoke('initialize', [
                    'intervalTime' => MathUtility::forceIntegerInRange((int)$GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'] - 60, 60),
                    'requestTokenUrl' => (string)$this->uriBuilder->buildUriFromRoute('login_request_token'),
                    'loginFramesetUrl' => (string)$this->uriBuilder->buildUriFromRoute('login_frameset'),
                    'logoutUrl' => (string)$this->uriBuilder->buildUriFromRoute('logout'),
                ])
        );
        $javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/backend/broadcast-service.js')->invoke('listen')
        );
        // load the storage API and fill the UC into the PersistentStorage, so no additional AJAX call is needed
        $javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/backend/storage/persistent.js')
                ->invoke('load', $backendUser->uc)
        );
        $javaScriptRenderer->addGlobalAssignment([
            'TYPO3' => [
                'configuration' => [
                    'username' => htmlspecialchars($backendUser->user['username']),
                    'showRefreshLoginPopup' => (bool)($GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup'] ?? false),
                ],
            ],
        ]);
        $javaScriptRenderer->includeTaggedImports('backend.module');
        $javaScriptRenderer->includeTaggedImports('backend.navigation-component');

        // @todo: This loads a ton of labels into JS. This should be reviewed what is really needed.
        //        This could happen when the localization API gets an overhaul.
        $pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf');
        $pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
        $pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');
        $pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');
        $pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/wizard.xlf');

        // @todo: We can not put this into the template since PageRendererViewHelper does not deal with namespace in addInlineSettings argument
        $pageRenderer->addInlineSetting('ShowItem', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('show_item'));
        $pageRenderer->addInlineSetting('RecordHistory', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_history'));
        $pageRenderer->addInlineSetting('NewRecord', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('db_new'));
        $pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_edit'));
        $pageRenderer->addInlineSetting('RecordCommit', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('tce_db'));
        $pageRenderer->addInlineSetting('FileCommit', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('tce_file'));
        $pageRenderer->addInlineSetting('Clipboard', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('clipboard_process'));
        $dateFormat = ['dd-MM-yyyy', 'HH:mm dd-MM-yyyy'];
        // Needed for FormEngine manipulation (date picker)
        $pageRenderer->addInlineSetting('DateTimePicker', 'DateFormat', $dateFormat);
        $typo3Version = 'TYPO3 CMS ' . $this->typo3Version->getVersion();
        $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ? $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . ' [' . $typo3Version . ']' : $typo3Version;
        $pageRenderer->setTitle($title);

        $view = $this->viewFactory->create($request);
        $this->assignTopbarDetailsToView($request, $view);
        $view->assignMultiple([
            'modules' => $this->modules,
            'modulesCollapsed' => $this->getCollapseStateOfMenu(),
            'modulesInformation' => GeneralUtility::jsonEncodeForHtmlAttribute($this->getModulesInformation(), false),
            'startupModule' => $this->getStartupModule($request),
            'stateTracker' => (string)$this->uriBuilder->buildUriFromRoute('state-tracker'),
            'sitename' => $title,
            'sitenameFirstInBackendTitle' => ($backendUser->uc['backendTitleFormat'] ?? '') === 'sitenameFirst',
        ]);
        $content = $view->render('Backend/Main');
        $content = $this->eventDispatcher->dispatch(new AfterBackendPageRenderEvent($content, $view))->getContent();
        $pageRenderer->addBodyContent('<body>' . $content);
        return $pageRenderer->renderResponse();
    }

    /**
     * Returns the main module menu as json encoded HTML string. Used when
     * "update signals" request a menu reload, e.g. when an extension is loaded
     * that brings new main modules.
     */
    public function getModuleMenu(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->viewFactory->create($request);
        $view->assignMultiple([
            'modulesInformation' => GeneralUtility::jsonEncodeForHtmlAttribute($this->getModulesInformation(), false),
            'modules' => $this->modules,
        ]);
        return new JsonResponse(['menu' => $view->render('Backend/ModuleMenu')]);
    }

    /**
     * Returns the toolbar as json encoded HTML string. Used when
     * "update signals" request a toolbar reload, e.g. when an extension is loaded.
     */
    public function getTopbar(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->viewFactory->create($request);
        $this->assignTopbarDetailsToView($request, $view);
        return new JsonResponse(['topbar' => $view->render('Backend/Topbar')]);
    }

    /**
     * Renders the topbar, containing the backend logo, sitename etc.
     */
    protected function assignTopbarDetailsToView(ServerRequestInterface $request, ViewInterface $view): void
    {
        // Extension Configuration to find the TYPO3 logo in the left corner
        $extConf = $this->extensionConfiguration->get('backend');
        $logoPath = '';
        if (!empty($extConf['backendLogo'])) {
            $customBackendLogo = GeneralUtility::getFileAbsFileName(ltrim($extConf['backendLogo'], '/'));
            if (!empty($customBackendLogo)) {
                $logoPath = $customBackendLogo;
            }
        }
        // if no custom logo was set or the path is invalid, use the original one
        if (empty($logoPath) || !file_exists($logoPath)) {
            $logoPath = GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Public/Images/typo3_logo_orange.svg');
            $logoWidth = 22;
            $logoHeight = 22;
        } else {
            // set width/height for custom logo
            $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $logoPath);
            $logoWidth = $imageInfo->getWidth() ?: 22;
            $logoHeight = $imageInfo->getHeight() ?: 22;

            // High-resolution?
            if (str_contains($logoPath, '@2x.')) {
                $logoWidth /= 2;
                $logoHeight /= 2;
            }
        }
        $view->assign('hasModules', (bool)$this->modules);
        $view->assign('logoUrl', PathUtility::getAbsoluteWebPath($logoPath));
        $view->assign('logoWidth', $logoWidth);
        $view->assign('logoHeight', $logoHeight);
        $view->assign('applicationVersion', $this->typo3Version->getVersion());
        $view->assign('siteName', $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
        $view->assign('toolbarItems', $this->getToolbarItems($request));
        $view->assign('isInWorkspace', $this->getBackendUser()->workspace > 0);
    }

    /**
     * @return ToolbarItemInterface[]
     */
    protected function getToolbarItems(ServerRequestInterface $request): array
    {
        return array_map(static function (ToolbarItemInterface $toolbarItem) use ($request) {
            if ($toolbarItem instanceof RequestAwareToolbarItemInterface) {
                $toolbarItem->setRequest($request);
            }
            return $toolbarItem;
        }, array_filter(
            $this->toolbarItemsRegistry->getToolbarItems(),
            static fn(ToolbarItemInterface $toolbarItem) => $toolbarItem->checkAccess()
        ));
    }

    /**
     * Sets the startup module from either "redirect" GET parameters or user configuration.
     */
    protected function getStartupModule(ServerRequestInterface $request): array
    {
        $startModule = null;
        $startModuleIdentifier = null;
        $inaccessibleRedirectModule = null;
        $moduleParameters = [];
        try {
            $redirect = RouteRedirect::createFromRequest($request);
            if ($redirect !== null && $request->getMethod() === 'GET') {
                // Only redirect to existing non-ajax routes with no restriction to a specific method
                $redirect->resolve(GeneralUtility::makeInstance(Router::class));
                if ($this->isSpecialNoModuleRoute($redirect->getName())
                    || $this->moduleProvider->accessGranted($redirect->getName(), $this->getBackendUser())
                ) {
                    // Only add start module from request in case user has access or it's a no module route,
                    // e.g. to FormEngine where permissions are checked by the corresponding component.
                    // Access might temporarily be blocked. e.g. due to being in a workspace.
                    $startModuleIdentifier = $redirect->getName();
                    $moduleParameters = $redirect->getParameters();
                } elseif ($this->moduleProvider->isModuleRegistered($redirect->getName())) {
                    // A redirect is set, however, the user is not allowed to access the module.
                    // Store the requested module to later inform the user about the forced redirect.
                    $inaccessibleRedirectModule = $this->moduleProvider->getModule($redirect->getName());
                }
            }
        } finally {
            // No valid redirect, check for the start module
            if (!$startModuleIdentifier) {
                $backendUser = $this->getBackendUser();
                // start module on first login, will be removed once used the first time
                if (isset($backendUser->uc['startModuleOnFirstLogin'])) {
                    $startModuleIdentifier = $backendUser->uc['startModuleOnFirstLogin'];
                    unset($backendUser->uc['startModuleOnFirstLogin']);
                    $backendUser->writeUC();
                } elseif (isset($backendUser->uc['startModule']) && $this->moduleProvider->accessGranted($backendUser->uc['startModule'], $backendUser)) {
                    $startModuleIdentifier = $backendUser->uc['startModule'];
                } elseif ($firstAccessibleModule = $this->moduleProvider->getFirstAccessibleModule($backendUser)) {
                    $startModuleIdentifier = $firstAccessibleModule->getIdentifier();
                }

                // check if the start module has additional parameters, so a redirect to a specific
                // action is possible
                if (is_string($startModuleIdentifier) && str_contains($startModuleIdentifier, '->')) {
                    [$startModuleIdentifier, $startModuleParameters] = explode('->', $startModuleIdentifier, 2);
                    // if no GET parameters are set, check if there are parameters given from the UC
                    if (!$moduleParameters && $startModuleParameters) {
                        $moduleParameters = $startModuleParameters;
                    }
                }
            }
        }
        if ($startModuleIdentifier) {
            if ($this->moduleProvider->isModuleRegistered($startModuleIdentifier)) {
                // startModuleIdentifier may be an alias, resolve original module
                $startModule = $this->moduleProvider->getModule($startModuleIdentifier, $this->getBackendUser());
                $startModuleIdentifier = $startModule?->getIdentifier();
            }
            if (is_array($moduleParameters)) {
                $parameters = $moduleParameters;
            } else {
                $parameters = [];
                parse_str($moduleParameters, $parameters);
            }
            try {
                $deepLink = $this->uriBuilder->buildUriFromRoute($startModuleIdentifier, $parameters);
                if ($startModule !== null && $inaccessibleRedirectModule !== null) {
                    $this->enqueueRedirectMessage($startModule, $inaccessibleRedirectModule);
                }
                return [$startModuleIdentifier, (string)$deepLink];
            } catch (RouteNotFoundException $e) {
                // It might be, that the user does not have access to the
                // $startModule, e.g. for modules with workspace restrictions.
            }
        }
        return [null, null];
    }

    /**
     * Returns information for each registered and allowed module. Used by various JS components.
     */
    protected function getModulesInformation(): array
    {
        $modules = [];
        foreach ($this->moduleProvider->getModules(user: $this->getBackendUser(), grouped: false) as $identifier => $module) {
            $menuModule = new MenuModule(clone $module);
            $modules[$identifier] = [
                'name' => $identifier,
                'component' => $menuModule->getComponent(),
                'navigationComponentId' => $menuModule->getNavigationComponent(),
                'parent' => $menuModule->hasParentModule() ? $menuModule->getParentIdentifier() : '',
                'link' => $menuModule->getShouldBeLinked() ? (string)$this->uriBuilder->buildUriFromRoute($module->getIdentifier()) : '',
            ];
        }

        return $modules;
    }

    protected function getCollapseStateOfMenu(): bool
    {
        $backendUser = $this->getBackendUser();
        $uc = json_decode((string)json_encode($backendUser->uc), true);
        $collapseState = $uc['BackendComponents']['States']['typo3-module-menu']['collapsed'] ?? false;
        return $collapseState === true || $collapseState === 'true';
    }

    protected function enqueueRedirectMessage(ModuleInterface $requestedModule, ModuleInterface $redirectedModule): void
    {
        $languageService = $this->getLanguageService();
        $this->flashMessageService
            ->getMessageQueueByIdentifier(FlashMessageQueue::NOTIFICATION_QUEUE)
            ->enqueue(
                new FlashMessage(
                    sprintf(
                        $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:module.noAccess.message'),
                        $languageService->sL($redirectedModule->getTitle()),
                        $languageService->sL($requestedModule->getTitle())
                    ),
                    $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:module.noAccess.title'),
                    ContextualFeedbackSeverity::INFO,
                    true
                )
            );
    }

    /**
     * Check if given route identifier is a special "no module" route
     */
    protected function isSpecialNoModuleRoute(string $routeIdentifier): bool
    {
        return in_array($routeIdentifier, ['record_edit', 'file_edit'], true);
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
