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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Dashboard\Dashboard;
use TYPO3\CMS\Dashboard\DashboardInitializationService;
use TYPO3\CMS\Dashboard\DashboardPreset;
use TYPO3\CMS\Dashboard\DashboardPresetRegistry;
use TYPO3\CMS\Dashboard\DashboardRepository;
use TYPO3\CMS\Dashboard\WidgetGroupInitializationService;
use TYPO3\CMS\Extbase\Mvc\Controller\Exception\RequiredArgumentMissingException;

/**
 * @internal
 */
class DashboardController
{
    protected Dashboard $currentDashboard;

    public function __construct(
        protected readonly PageRenderer $pageRenderer,
        protected readonly UriBuilder $uriBuilder,
        protected readonly DashboardPresetRegistry $dashboardPresetRepository,
        protected readonly DashboardRepository $dashboardRepository,
        protected readonly DashboardInitializationService $dashboardInitializationService,
        protected readonly WidgetGroupInitializationService $widgetGroupInitializationService,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {}

    /**
     * Main entry method: Dispatch to other actions - those method names that end with "Action".
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->dashboardInitializationService->initializeDashboards($request, $this->getBackendUser());
        $this->currentDashboard = $this->dashboardInitializationService->getCurrentDashboard();
        $action = $request->getQueryParams()['action'] ?? $request->getParsedBody()['action'] ?? 'main';
        return $this->{$action . 'Action'}($request);
    }

    /**
     * This action is responsible for the main view of the dashboard and is just adding all collected data to the view.
     */
    protected function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $this->preparePageRenderer();
        $this->addFrontendResources();
        $view->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:dashboard/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            $this->currentDashboard->getTitle()
        );
        $view->assignMultiple([
            'availableDashboards' => $this->dashboardInitializationService->getDashboardsForUser(),
            'dashboardPresets' => $this->dashboardPresetRepository->getDashboardPresets(),
            'widgetGroups' => $this->widgetGroupInitializationService->buildWidgetGroupsConfiguration(),
            'currentDashboard' => $this->currentDashboard,
            'addWidgetUri' => (string)$this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'addWidget']),
            'addDashboardUri' => (string)$this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'addDashboard']),
            'deleteDashboardUri' => (string)$this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'deleteDashboard']),
            'configureDashboardUri' => (string)$this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'configureDashboard']),
        ]);
        return $view->renderResponse('Dashboard/Main');
    }

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

    protected function setActiveDashboardAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->saveCurrentDashboard((string)($request->getQueryParams()['currentDashboard'] ?? ''));
        $route = $this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'main']);
        return new RedirectResponse($route);
    }

    protected function addDashboardAction(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getParsedBody();
        $dashboardIdentifier = (string)($parameters['dashboard'] ?? '');
        $dashboardPreset = $this->dashboardPresetRepository->getDashboardPresets()[$dashboardIdentifier] ?? null;
        if ($dashboardPreset instanceof DashboardPreset) {
            $dashboard = $this->dashboardRepository->create(
                $dashboardPreset,
                (int)$this->getBackendUser()->user['uid'],
                $parameters['dashboard-title'] ?? ''
            );
            if ($dashboard !== null) {
                $this->saveCurrentDashboard($dashboard->getIdentifier());
            }
        }
        return new RedirectResponse($this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'main']));
    }

    protected function deleteDashboardAction(): ResponseInterface
    {
        $this->dashboardRepository->delete($this->currentDashboard);
        return new RedirectResponse($this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'main']));
    }

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

    protected function removeWidgetAction(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getQueryParams();
        $widgetHash = $parameters['widgetHash'] ?? '';
        $widgets = $this->currentDashboard->getWidgetConfig();
        if ($widgetHash !== '' && array_key_exists($widgetHash, $widgets)) {
            unset($widgets[$widgetHash]);
            $this->dashboardRepository->updateWidgetConfig($this->currentDashboard, $widgets);
        }
        $route = $this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'main']);
        return new RedirectResponse($route);
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
        foreach ($this->dashboardInitializationService->getRequireJsModules() as $requireJsModule) {
            if (is_array($requireJsModule)) {
                // Deprecation message is triggered by DashboardInitializationService::defineResourcesOfWidgets, and therefore silenced here.
                $this->pageRenderer->loadRequireJsModule($requireJsModule[0], $requireJsModule[1], true);
            } else {
                // Deprecation message is triggered by DashboardInitializationService::defineResourcesOfWidgets, and therefore silenced here.
                $javaScriptRenderer->addJavaScriptModuleInstruction(
                    JavaScriptModuleInstruction::forRequireJS($requireJsModule, null, true)
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
        $this->pageRenderer->loadJavaScriptModule('muuri');
        $this->pageRenderer->loadJavaScriptModule('@typo3/dashboard/grid.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/dashboard/widget-content-collector.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/dashboard/widget-selector.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/dashboard/widget-refresh.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/dashboard/widget-remover.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/dashboard/dashboard-modal.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/dashboard/dashboard-delete.js');
        $this->pageRenderer->addCssFile('EXT:dashboard/Resources/Public/Css/dashboard.css');
    }

    protected function saveCurrentDashboard(string $identifier): void
    {
        $this->getBackendUser()->pushModuleData('dashboard/current_dashboard/', $identifier);
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
