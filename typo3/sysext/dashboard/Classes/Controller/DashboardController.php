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

namespace TYPO3\CMS\Dashboard\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException as RouteNotFoundExceptionAlias;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Dashboard\Dashboard;
use TYPO3\CMS\Dashboard\DashboardInitializationService;
use TYPO3\CMS\Dashboard\DashboardPresetRegistry;
use TYPO3\CMS\Dashboard\DashboardRepository;
use TYPO3\CMS\Dashboard\WidgetGroupInitializationService;
use TYPO3\CMS\Extbase\Mvc\Controller\Exception\RequiredArgumentMissingException;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * @internal
 */
class DashboardController extends AbstractController
{
    protected PageRenderer $pageRenderer;
    protected UriBuilder $uriBuilder;
    protected Dashboard $currentDashboard;
    protected DashboardPresetRegistry $dashboardPresetRepository;
    protected DashboardRepository $dashboardRepository;
    protected DashboardInitializationService $dashboardInitializationService;
    protected WidgetGroupInitializationService $widgetGroupInitializationService;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    private ?ModuleTemplate $moduleTemplate = null;
    protected StandaloneView $view;

    public function __construct(
        PageRenderer $pageRenderer,
        UriBuilder $uriBuilder,
        DashboardPresetRegistry $dashboardPresetRepository,
        DashboardRepository $dashboardRepository,
        DashboardInitializationService $dashboardInitializationService,
        WidgetGroupInitializationService $widgetGroupInitializationService,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->pageRenderer = $pageRenderer;
        $this->uriBuilder = $uriBuilder;
        $this->dashboardPresetRepository = $dashboardPresetRepository;
        $this->dashboardRepository = $dashboardRepository;
        $this->dashboardInitializationService = $dashboardInitializationService;

        $this->dashboardInitializationService->initializeDashboards($this->getBackendUser());
        $this->currentDashboard = $this->dashboardInitializationService->getCurrentDashboard();
        $this->widgetGroupInitializationService = $widgetGroupInitializationService;

        $this->moduleTemplateFactory = $moduleTemplateFactory;

        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
    }

    /**
     * This action is responsible for the main view of the dashboard and is just adding all collected data
     * to the view
     *
     * @throws RouteNotFoundExceptionAlias
     */
    protected function mainAction(): void
    {
        $this->moduleTemplate->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:dashboard/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            $this->currentDashboard->getTitle()
        );

        $this->view->assignMultiple([
            'availableDashboards' => $this->dashboardInitializationService->getDashboardsForUser(),
            'dashboardPresets' => $this->dashboardPresetRepository->getDashboardPresets(),
            'widgetGroups' => $this->widgetGroupInitializationService->buildWidgetGroupsConfiguration(),
            'currentDashboard' => $this->currentDashboard,
            'addWidgetUri' => (string)$this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'addWidget']),
            'addDashboardUri' => (string)$this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'addDashboard']),
            'deleteDashboardUri' => (string)$this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'deleteDashboard']),
            'configureDashboardUri' => (string)$this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'configureDashboard']),
        ]);
    }

    /**
     * Main entry method: Dispatch to other actions - those method names that end with "Action".
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->preparePageRenderer();

        $action = $request->getQueryParams()['action'] ?? $request->getParsedBody()['action'] ?? 'main';
        $this->initializeView('Dashboard/' . ucfirst($action));
        $result = $this->{$action . 'Action'}($request);
        if ($result instanceof ResponseInterface) {
            return $result;
        }
        $this->addFrontendResources();
        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws RouteNotFoundExceptionAlias
     */
    protected function configureDashboardAction(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getParsedBody();
        $currentDashboard = $parameters['currentDashboard'] ?? '';
        $route = $this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'main'], UriBuilder::ABSOLUTE_URL);

        if ($currentDashboard !== '' && isset($parameters['dashboard'])) {
            $this->dashboardRepository->updateDashboardSettings($currentDashboard, $parameters['dashboard']);
        }

        return new RedirectResponse($route);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws RouteNotFoundExceptionAlias
     */
    protected function setActiveDashboardAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->saveCurrentDashboard($request->getQueryParams()['currentDashboard']);
        $route = $this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'main']);
        return new RedirectResponse($route);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws RouteNotFoundExceptionAlias
     */
    protected function addDashboardAction(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getParsedBody();
        $dashboardIdentifier = $parameters['dashboard'] ?? '';

        if ($dashboardIdentifier !== '') {
            $dashboard = $this->dashboardRepository->create($this->dashboardPresetRepository->getDashboardPresets()[$dashboardIdentifier], (int)$this->getBackendUser()->user['uid'], $parameters['dashboard-title']);

            if ($dashboard instanceof Dashboard) {
                $this->saveCurrentDashboard($dashboard->getIdentifier());
            }
        }

        return new RedirectResponse($this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'main']));
    }

    /**
     * @return ResponseInterface
     * @throws RouteNotFoundExceptionAlias
     */
    protected function deleteDashboardAction(): ResponseInterface
    {
        $this->dashboardRepository->delete($this->currentDashboard);
        return new RedirectResponse($this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'main']));
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws RouteNotFoundExceptionAlias
     * @throws RequiredArgumentMissingException
     */
    protected function addWidgetAction(ServerRequestInterface $request): ResponseInterface
    {
        $widgetKey = (string)($request->getQueryParams()['widget'] ?? '');

        if ($widgetKey === '') {
            throw new RequiredArgumentMissingException('Argument "widget" not set.', 1624436360);
        }

        $widgets = $this->currentDashboard->getWidgetConfig();
        $hash = sha1($widgetKey . '-' . time());
        $widgets[$hash] = ['identifier' => $widgetKey];
        $this->dashboardRepository->updateWidgetConfig($this->currentDashboard, $widgets);

        $route = $this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'main']);
        return new RedirectResponse($route);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws RouteNotFoundExceptionAlias
     */
    protected function removeWidgetAction(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getQueryParams();
        $widgetHash = $parameters['widgetHash'];
        $widgets = $this->currentDashboard->getWidgetConfig();

        if (array_key_exists($widgetHash, $widgets)) {
            unset($widgets[$widgetHash]);
            $this->dashboardRepository->updateWidgetConfig($this->currentDashboard, $widgets);
        }
        $route = $this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'main']);
        return new RedirectResponse($route);
    }

    /**
     * Sets up the Fluid View.
     *
     * @param string $templateName
     */
    protected function initializeView(string $templateName): void
    {
        $this->view->setTemplate($templateName);
        $this->view->getRenderingContext()->getTemplatePaths()->fillDefaultsByPackageName('dashboard');
        $this->moduleTemplate->getDocHeaderComponent()->disable();
    }

    /**
     * Adds CSS and JS files that are necessary for widgets to the page renderer
     */
    protected function addFrontendResources(): void
    {
        $javaScriptRenderer = $this->pageRenderer->getJavaScriptRenderer();
        foreach ($this->dashboardInitializationService->getJavaScriptModuleInstructions() as $instruction) {
            $javaScriptRenderer->addJavaScriptModuleInstruction($instruction);
        }
        // @todo Deprecate `RequireJsModuleInterface` in TYPO3 v12.0
        foreach ($this->dashboardInitializationService->getRequireJsModules() as $requireJsModule) {
            if (is_array($requireJsModule)) {
                $this->pageRenderer->loadRequireJsModule($requireJsModule[0], $requireJsModule[1]);
            } else {
                $javaScriptRenderer->addJavaScriptModuleInstruction(
                    JavaScriptModuleInstruction::forRequireJS($requireJsModule)
                );
            }
        }
        foreach ($this->dashboardInitializationService->getCssFiles() as $cssFile) {
            $this->pageRenderer->addCssFile($cssFile);
        }
        foreach ($this->dashboardInitializationService->getJsFiles() as $jsFile) {
            $this->pageRenderer->addJsFile($jsFile);
        }
    }

    /**
     * Add the CSS and JS of the dashboard module to the page renderer
     */
    protected function preparePageRenderer(): void
    {
        $publicResourcesPath = PathUtility::getPublicResourceWebPath('EXT:dashboard/Resources/Public/');
        $this->pageRenderer->addRequireJsConfiguration(
            [
                'paths' => [
                    'muuri' => $publicResourcesPath . 'JavaScript/Contrib/muuri',
                    'web-animate' => $publicResourcesPath . 'JavaScript/Contrib/web-animate',
                ],
            ]
        );

        $this->pageRenderer->loadRequireJsModule('muuri');
        $this->pageRenderer->loadRequireJsModule('web-animate');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Dashboard/Grid');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Dashboard/WidgetContentCollector');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Dashboard/WidgetSelector');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Dashboard/WidgetRefresh');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Dashboard/WidgetRemover');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Dashboard/DashboardModal');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Dashboard/DashboardDelete');
        $this->pageRenderer->addCssFile('EXT:dashboard/Resources/Public/Css/dashboard.css');
    }
}
