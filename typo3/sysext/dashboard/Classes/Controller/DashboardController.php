<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Dashboard\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException as RouteNotFoundExceptionAlias;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Dashboard\Dashboard;
use TYPO3\CMS\Dashboard\DashboardPreset;
use TYPO3\CMS\Dashboard\DashboardPresetRegistry;
use TYPO3\CMS\Dashboard\DashboardRepository;
use TYPO3\CMS\Dashboard\WidgetGroupRegistry;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use TYPO3\CMS\Dashboard\Widgets\Interfaces\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\Interfaces\AdditionalJavaScriptInterface;
use TYPO3\CMS\Dashboard\Widgets\Interfaces\RequireJsModuleInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class DashboardController extends AbstractController
{
    /**
     * @var ModuleTemplate
     */
    private $moduleTemplate;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @var ViewInterface
     */
    protected $view;

    /**
     * @var array
     */
    protected $cssFiles = [];

    /**
     * @var array
     */
    protected $jsFiles = [];

    protected $requireJsModules = [];

    /**
     * @var Dashboard
     */
    protected $currentDashboard;

    /**
     * @var Dashboard[]
     */
    protected $dashboardsForCurrentUser;

    /**
     * @var DashboardPreset[]
     */
    protected $availableDashboardPresets;

    /**
     * @var DashboardPresetRegistry
     */
    protected $dashboardPresetRepository;

    /**
     * @var DashboardRepository
     */
    protected $dashboardRepository;

    /**
     * @var WidgetGroupRegistry
     */
    protected $widgetGroupRepository;

    /**
     * @var WidgetRegistry
     */
    protected $widgetRegistry;

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    public function __construct(
        ModuleTemplate $moduleTemplate,
        UriBuilder $uriBuilder,
        DashboardPresetRegistry $dashboardPresetRepository,
        DashboardRepository $dashboardRepository,
        WidgetGroupRegistry $widgetGroupRepository,
        WidgetRegistry $widgetRegistry
    ) {
        $this->moduleTemplate = $moduleTemplate;
        $this->uriBuilder = $uriBuilder;
        $this->dashboardPresetRepository = $dashboardPresetRepository;
        $this->dashboardRepository = $dashboardRepository;
        $this->widgetGroupRepository = $widgetGroupRepository;
        $this->widgetRegistry = $widgetRegistry;

        $this->initializeDashboardsForCurrentUser();
    }

    protected function initializeDashboardsForCurrentUser(): void
    {
        $this->dashboardsForCurrentUser = $this->getDashboardsForCurrentUser();
        $this->currentDashboard = $this->dashboardRepository->getDashboardByIdentifier($this->loadCurrentDashboard());

        $this->availableDashboardPresets = $this->dashboardPresetRepository->getDashboardPresets();

        if (empty($this->dashboardsForCurrentUser)) {
            $this->dashboardsForCurrentUser = [];

            $userConfig = $this->getBackendUser()->getTSConfig();
            $dashboardsToCreate = GeneralUtility::trimExplode(
                ',',
                $userConfig['options.']['dashboard.']['dashboardPresetsForNewUsers'] ?? 'default'
            );

            /** @var DashboardPreset $dashboardPreset */
            foreach ($this->availableDashboardPresets as $dashboardPreset) {
                if (in_array($dashboardPreset->getIdentifier(), $dashboardsToCreate, true)) {
                    $dashboard = $this->dashboardRepository->create(
                        $dashboardPreset,
                        (int)$this->getBackendUser()->user['uid']
                    );

                    if ($dashboard instanceof Dashboard) {
                        $this->dashboardsForCurrentUser[$dashboard->getIdentifier()] = $dashboard;
                    }
                }
            }
        }

        if (!$this->currentDashboard instanceof Dashboard) {
            $this->currentDashboard = reset($this->dashboardsForCurrentUser);
            $this->saveCurrentDashboard($this->currentDashboard->getIdentifier());
        }

        $this->currentDashboard->initializeWidgets();
        $this->defineResourcesOfWidgets($this->currentDashboard->getWidgets());
    }

    /**
     * This action is responsible for the main view of the dashboard and is just adding all collected data
     * to the view
     *
     * @throws RouteNotFoundExceptionAlias
     */
    public function mainAction(): void
    {
        $this->view->assignMultiple([
            'availableDashboards' => $this->dashboardsForCurrentUser,
            'dashboardPresets' => $this->availableDashboardPresets,
            'widgetGroups' => $this->buildWidgetGroupsConfiguration(),
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
        $pageRenderer = $this->moduleTemplate->getPageRenderer();
        $this->preparePageRenderer($pageRenderer);

        $action = $request->getQueryParams()['action'] ?? $request->getParsedBody()['action'] ?? 'main';
        $this->initializeView('Dashboard/' . ucfirst($action));
        $result = $this->{$action . 'Action'}($request);
        if ($result instanceof ResponseInterface) {
            return $result;
        }
        $this->addFrontendResources($pageRenderer);
        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws RouteNotFoundExceptionAlias
     */
    public function configureDashboardAction(ServerRequestInterface $request): ResponseInterface
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
    public function setActiveDashboardAction(ServerRequestInterface $request): ResponseInterface
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
    public function addDashboardAction(ServerRequestInterface $request): ResponseInterface
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
    public function deleteDashboardAction(): ResponseInterface
    {
        $this->dashboardRepository->delete($this->currentDashboard);
        return new RedirectResponse($this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'main']));
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws RouteNotFoundExceptionAlias
     */
    public function addWidgetAction(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getQueryParams();
        $widgetKey = $parameters['widget'];

        if ($widgetKey) {
            $widgets = $this->currentDashboard->getWidgetConfig();
            $hash = sha1($widgetKey . '-' . time());
            $widgets[$hash] = ['identifier' => $widgetKey];
            $this->dashboardRepository->updateWidgetConfig($this->currentDashboard, $widgets);
        }

        $route = $this->uriBuilder->buildUriFromRoute('dashboard', ['action' => 'main']);
        return new RedirectResponse($route);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws RouteNotFoundExceptionAlias
     */
    public function removeWidgetAction(ServerRequestInterface $request): ResponseInterface
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
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplate($templateName);

        $this->view->getRenderingContext()->getTemplatePaths()->fillDefaultsByPackageName('dashboard');
        $this->moduleTemplate->getDocHeaderComponent()->disable();
    }

    /**
     * Adds CSS and JS files that are necessary for widgets to the page renderer
     *
     * @param PageRenderer $pageRenderer
     */
    protected function addFrontendResources(PageRenderer $pageRenderer): void
    {
        foreach ($this->requireJsModules as $requireJsModule) {
            if (is_array($requireJsModule)) {
                $pageRenderer->loadRequireJsModule($requireJsModule[0], $requireJsModule[1]);
            } else {
                $pageRenderer->loadRequireJsModule($requireJsModule);
            }
        }
        foreach ($this->cssFiles as $cssFile) {
            $pageRenderer->addCssFile($cssFile);
        }
        foreach ($this->jsFiles as $jsFile) {
            $pageRenderer->addJsFile($jsFile);
        }
    }

    /**
     * Add the CSS and JS of the dashboard module to the page renderer
     *
     * @param PageRenderer $pageRenderer
     */
    protected function preparePageRenderer(PageRenderer $pageRenderer): void
    {
        $publicResourcesPath =
            PathUtility::getAbsoluteWebPath(ExtensionManagementUtility::extPath('dashboard')) . 'Resources/Public/';

        $pageRenderer->addRequireJsConfiguration(
            [
                'paths' => [
                    'muuri' => $publicResourcesPath . 'JavaScript/Contrib/muuri',
                    'web-animations' => $publicResourcesPath . 'JavaScript/Contrib/web-animations.min',
                ],
            ]
        );

        $pageRenderer->loadRequireJsModule('muuri');
        $pageRenderer->loadRequireJsModule('web-animations');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Dashboard/Grid');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Dashboard/WidgetContentCollector');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Dashboard/WidgetSelector');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Dashboard/WidgetRemover');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Dashboard/DashboardModal');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Dashboard/DashboardDelete');
        $pageRenderer->addCssFile($publicResourcesPath . 'Css/dashboard.css');
    }

    /**
     * @return Dashboard[]
     */
    protected function getDashboardsForCurrentUser(): array
    {
        $dashboards = [];
        foreach ($this->dashboardRepository->getDashboardsForUser((int)$this->getBackendUser()->user['uid']) as $dashboard) {
            $dashboards[$dashboard->getIdentifier()] = $dashboard;
        }
        return $dashboards;
    }

    /**
     * Define the different groups of widgets as shown in the modal when adding a widget to the current dashboard
     *
     * @return array
     */
    protected function buildWidgetGroupsConfiguration(): array
    {
        $groupConfigurations = [];
        foreach ($this->widgetGroupRepository->getWidgetGroups() as $widgetGroup) {
            $widgetInstances = [];
            $widgetGroupIdentifier = $widgetGroup->getIdentifier();

            $widgetsForGroup = $this->widgetRegistry->getAvailableWidgetsForWidgetGroup($widgetGroupIdentifier);
            foreach ($widgetsForGroup as $identifier => $widgetService) {
                $widgetInstances[$identifier] = GeneralUtility::makeInstance($widgetService);
            }

            $groupConfigurations[$widgetGroupIdentifier] = [
                'identifier' => $widgetGroupIdentifier,
                'title' => $widgetGroup->getTitle(),
                'widgets' => $widgetInstances
            ];
        }

        return $groupConfigurations;
    }

    /**
     * @param array $widgets
     */
    protected function defineResourcesOfWidgets(array $widgets): void
    {
        foreach ($widgets as $widget) {
            if ($widget instanceof RequireJsModuleInterface) {
                $this->defineRequireJsModules($widget);
            }
            if ($widget instanceof AdditionalCssInterface) {
                $this->defineCssFiles($widget);
            }
            if ($widget instanceof AdditionalJavaScriptInterface) {
                $this->defineJsFiles($widget);
            }
        }
    }

    /**
     * Add the RequireJS modules needed by some widgets
     *
     * @param RequireJsModuleInterface $widgetInstance
     */
    protected function defineRequireJsModules(RequireJsModuleInterface $widgetInstance): void
    {
        foreach ($widgetInstance->getRequireJsModules() as $moduleNameOrIndex => $callbackOrModuleName) {
            if (is_string($moduleNameOrIndex)) {
                $this->requireJsModules[] = [$moduleNameOrIndex, $callbackOrModuleName];
            } else {
                $this->requireJsModules[] = $callbackOrModuleName;
            }
        }
    }

    /**
     * Define the correct path of the JS files of a widget and add them to the list of JS files that needs to be
     * included
     *
     * @param AdditionalJavaScriptInterface $widgetInstance
     */
    protected function defineJsFiles(AdditionalJavaScriptInterface $widgetInstance): void
    {
        foreach ($widgetInstance->getJsFiles() as $key => $jsFile) {
            if (strpos($jsFile, 'EXT:') === 0) {
                $jsFile = PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName($jsFile));
            }
            $this->jsFiles[$jsFile] = $jsFile;
        }
    }

    /**
     * Define the correct path of the CSS files of a widget and add them to the list of CSS files that needs to be
     * included
     *
     * @param AdditionalCssInterface $widgetInstance
     */
    protected function defineCssFiles(AdditionalCssInterface $widgetInstance): void
    {
        foreach ($widgetInstance->getCssFiles() as $cssFile) {
            if (strpos($cssFile, 'EXT:') === 0) {
                $cssFile = PathUtility::getAbsoluteWebPath(GeneralUtility::getFileAbsFileName($cssFile));
            }
            if (!in_array($cssFile, $this->cssFiles, true)) {
                $this->cssFiles[$cssFile] = $cssFile;
            }
        }
    }
}
