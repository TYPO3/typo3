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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\PageRendererBackendSetupTrait;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemsRegistry;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\View\ViewInterface;

/**
 * Class for rendering the TYPO3 backend.
 * This is the backend outer main frame with topbar and module menu.
 */
class BackendController
{
    use PageRendererBackendSetupTrait;

    protected string $css = '';

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
    ) {
        // @todo: This hook is essentially useless.
        $this->executeHook('constructPostProcess');
        $this->modules = $this->moduleProvider->getModulesForModuleMenu($this->getBackendUser());
    }

    /**
     * Main function generating the BE scaffolding.
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $pageRenderer = $this->pageRenderer;

        $this->executeHook('renderPreProcess');

        $this->setUpBasicPageRendererForBackend($pageRenderer, $this->extensionConfiguration, $request, $this->getLanguageService());

        $javaScriptRenderer = $pageRenderer->getJavaScriptRenderer();
        $javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/backend/login-refresh.js')
                ->invoke('initialize', [
                    'intervalTime' => MathUtility::forceIntegerInRange((int)$GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'] - 60, 60),
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
        $pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/debugger.xlf');
        $pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/wizard.xlf');

        // @todo: We can not put this into the template since PageRendererViewHelper does not deal with namespace in addInlineSettings argument
        $pageRenderer->addInlineSetting('ContextHelp', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('help_cshmanual'));
        $pageRenderer->addInlineSetting('ShowItem', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('show_item'));
        $pageRenderer->addInlineSetting('RecordHistory', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_history'));
        $pageRenderer->addInlineSetting('NewRecord', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('db_new'));
        $pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_edit'));
        $pageRenderer->addInlineSetting('RecordCommit', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('tce_db'));
        $pageRenderer->addInlineSetting('FileCommit', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('tce_file'));
        $pageRenderer->addInlineSetting('Clipboard', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('clipboard_process'));
        $dateFormat = ['DD-MM-Y', 'HH:mm DD-MM-Y'];
        // Needed for FormEngine manipulation (date picker)
        $pageRenderer->addInlineSetting('DateTimePicker', 'DateFormat', $dateFormat);
        $pageRenderer->addCssInlineBlock('BackendInlineCSS', $this->css);
        $typo3Version = 'TYPO3 CMS ' . $this->typo3Version->getVersion();
        $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ? $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . ' [' . $typo3Version . ']' : $typo3Version;
        $pageRenderer->setTitle($title);
        $moduleMenuCollapsed = $this->getCollapseStateOfMenu();

        $view = $this->viewFactory->create($request, 'typo3/cms-backend');
        $this->assignTopbarDetailsToView($view);
        $view->assignMultiple([
            'modules' => $this->modules,
            'startupModule' => $this->getStartupModule($request),
            'stateTracker' => (string)$this->uriBuilder->buildUriFromRoute('state-tracker'),
            'sitename' => $title,
            'sitenameFirstInBackendTitle' => ($backendUser->uc['backendTitleFormat'] ?? '') === 'sitenameFirst',
        ]);
        $content = $view->render('Backend/Main');
        $this->executeHook('renderPostProcess', ['content' => &$content]);
        $bodyTag = '<body class="scaffold t3js-scaffold' . (!$moduleMenuCollapsed && $this->modules ? ' scaffold-modulemenu-expanded' : '') . '">';
        $pageRenderer->addBodyContent($bodyTag . $content);
        return new HtmlResponse($pageRenderer->render());
    }

    /**
     * Returns the main module menu as json encoded HTML string. Used when
     * "update signals" request a menu reload, e.g. when an extension is loaded
     * that brings new main modules.
     */
    public function getModuleMenu(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->viewFactory->create($request, 'typo3/cms-backend');
        $view->assign('modules', $this->modules);
        return new JsonResponse(['menu' => $view->render('Backend/ModuleMenu')]);
    }

    /**
     * Returns the toolbar as json encoded HTML string. Used when
     * "update signals" request a toolbar reload, e.g. when an extension is loaded.
     */
    public function getTopbar(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->viewFactory->create($request, 'typo3/cms-backend');
        $this->assignTopbarDetailsToView($view);
        return new JsonResponse(['topbar' => $view->render('Backend/Topbar')]);
    }

    /**
     * Adds a css snippet to the backend. This method is old and its purpose
     * seems to be that hooks (see executeHook()) can add css?
     * @todo: Candidate for deprecation / removal.
     */
    public function addCss(string $css): void
    {
        $this->css .= $css;
    }

    /**
     * Renders the topbar, containing the backend logo, sitename etc.
     */
    protected function assignTopbarDetailsToView(ViewInterface $view): void
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
            $logoWidth = $imageInfo->getWidth() ?? '22';
            $logoHeight = $imageInfo->getHeight() ?? '22';

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
        $view->assign('toolbar', $this->renderToolbar());
    }

    /**
     * Renders the items in the top toolbar.
     *
     * @todo: Inline this to the topbar template
     */
    protected function renderToolbar(): string
    {
        $toolbarItems = $this->toolbarItemsRegistry->getToolbarItems();
        $toolbar = [];
        foreach ($toolbarItems as $toolbarItem) {
            /** @var ToolbarItemInterface $toolbarItem */
            if ($toolbarItem->checkAccess()) {
                $hasDropDown = (bool)$toolbarItem->hasDropDown();
                $additionalAttributes = (array)$toolbarItem->getAdditionalAttributes();

                $liAttributes = [];

                // Merge class: Add dropdown class if hasDropDown, add classes from additional attributes
                $classes = [];
                $classes[] = 'toolbar-item';
                $classes[] = 't3js-toolbar-item';
                if (isset($additionalAttributes['class'])) {
                    $classes[] = $additionalAttributes['class'];
                    unset($additionalAttributes['class']);
                }
                $liAttributes['class'] = implode(' ', $classes);

                // Add further attributes
                foreach ($additionalAttributes as $name => $value) {
                    $liAttributes[(string)$name] = (string)$value;
                }

                // Create a unique id from class name
                $fullyQualifiedClassName = \get_class($toolbarItem);
                $className = GeneralUtility::underscoredToLowerCamelCase($fullyQualifiedClassName);
                $className = GeneralUtility::camelCaseToLowerCaseUnderscored($className);
                $className = str_replace(['_', '\\'], '-', $className);
                $liAttributes['id'] = $className;

                // Create data attribute identifier
                $shortName = substr($fullyQualifiedClassName, (int)strrpos($fullyQualifiedClassName, '\\') + 1);
                $dataToolbarIdentifier = GeneralUtility::camelCaseToLowerCaseUnderscored($shortName);
                $dataToolbarIdentifier = str_replace('_', '-', $dataToolbarIdentifier);
                $liAttributes['data-toolbar-identifier'] = $dataToolbarIdentifier;

                $toolbar[] = '<li ' . GeneralUtility::implodeAttributes($liAttributes, true) . '>';

                if ($hasDropDown) {
                    $toolbar[] = '<a href="#" class="toolbar-item-link dropdown-toggle" data-bs-toggle="dropdown" data-bs-offset="0,-2">';
                    $toolbar[] = $toolbarItem->getItem();
                    $toolbar[] = '</a>';
                    $toolbar[] = '<div class="dropdown-menu" role="menu">';
                    $toolbar[] = $toolbarItem->getDropDown();
                    $toolbar[] = '</div>';
                } else {
                    $toolbar[] = $toolbarItem->getItem();
                }
                $toolbar[] = '</li>';
            }
        }
        return implode(LF, $toolbar);
    }

    /**
     * Sets the startup module from either "redirect" GET parameters or user configuration.
     */
    protected function getStartupModule(ServerRequestInterface $request): array
    {
        $startModule = null;
        $moduleParameters = [];
        try {
            $redirect = RouteRedirect::createFromRequest($request);
            if ($request->getMethod() === 'GET' && $redirect !== null) {
                // Only redirect to existing non-ajax routes with no restriction to a specific method
                $redirect->resolve(GeneralUtility::makeInstance(Router::class));
                $startModule = $redirect->getName();
                $moduleParameters = $redirect->getParameters();
            }
        } finally {
            // No valid redirect, check for the start module
            if (!$startModule) {
                $backendUser = $this->getBackendUser();
                // start module on first login, will be removed once used the first time
                if (isset($backendUser->uc['startModuleOnFirstLogin'])) {
                    $startModule = $backendUser->uc['startModuleOnFirstLogin'];
                    unset($backendUser->uc['startModuleOnFirstLogin']);
                    $backendUser->writeUC();
                } elseif (isset($backendUser->uc['startModule']) && $this->moduleProvider->accessGranted($backendUser->uc['startModule'], $backendUser)) {
                    $startModule = $backendUser->uc['startModule'];
                } elseif ($firstAccessibleModule = $this->moduleProvider->getFirstAccessibleModule($backendUser)) {
                    $startModule = $firstAccessibleModule->getIdentifier();
                }

                // check if the start module has additional parameters, so a redirect to a specific
                // action is possible
                if (is_string($startModule) && str_contains($startModule, '->')) {
                    [$startModule, $startModuleParameters] = explode('->', $startModule, 2);
                    // if no GET parameters are set, check if there are parameters given from the UC
                    if (!$moduleParameters && $startModuleParameters) {
                        $moduleParameters = $startModuleParameters;
                    }
                }
            }
        }
        if ($startModule) {
            if (is_array($moduleParameters)) {
                $parameters = $moduleParameters;
            } else {
                $parameters = [];
                parse_str($moduleParameters, $parameters);
            }
            $deepLink = $this->uriBuilder->buildUriFromRoute($startModule, $parameters);
            return [$startModule, (string)$deepLink];
        }
        return [null, null];
    }

    /**
     * Executes defined hooks functions for the given identifier.
     *
     * These hook identifiers are valid:
     * + constructPostProcess
     * + renderPreProcess
     * + renderPostProcess
     */
    protected function executeHook(string $identifier, array $hookConfiguration = []): void
    {
        $options = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php'];
        foreach ($options[$identifier] ?? [] as $hookFunction) {
            GeneralUtility::callUserFunction($hookFunction, $hookConfiguration, $this);
        }
    }

    protected function getCollapseStateOfMenu(): bool
    {
        $backendUser = $this->getBackendUser();
        $uc = json_decode((string)json_encode($backendUser->uc), true);
        $collapseState = $uc['BackendComponents']['States']['typo3-module-menu']['collapsed'] ?? false;
        return $collapseState === true || $collapseState === 'true';
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
