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
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Dto\Settings\EditableSetting;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Settings\Category;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Settings\SettingsDiff;
use TYPO3\CMS\Core\Settings\SettingsTypeRegistry;
use TYPO3\CMS\Dashboard\DashboardPreset;
use TYPO3\CMS\Dashboard\DashboardPresetRegistry;
use TYPO3\CMS\Dashboard\Factory\WidgetSettingsFactory;
use TYPO3\CMS\Dashboard\Repository\DashboardRepository;
use TYPO3\CMS\Dashboard\WidgetGroupInitializationService;
use TYPO3\CMS\Dashboard\WidgetRegistry;

/**
 * @internal
 */
#[AsController]
class DashboardAjaxController
{
    public function __construct(
        protected readonly DashboardRepository $dashboardRepository,
        protected readonly DashboardPresetRegistry $dashboardPresetRegistry,
        protected readonly WidgetRegistry $widgetRegistry,
        protected readonly WidgetGroupInitializationService $widgetGroupInitializationService,
        protected readonly WidgetSettingsFactory $widgetSettingsFactory,
        protected readonly SettingsTypeRegistry $settingsTypeRegistry,
        protected readonly UriBuilder $uriBuilder,
    ) {}

    public function getDashboards(ServerRequestInterface $request): ResponseInterface
    {
        $availableDashboards = $this->dashboardRepository->getDashboardsForUser($this->getBackendUser()->getUserId());
        $dashboards = [];
        foreach ($availableDashboards as $dashboard) {
            $dashboard->initializeWidgets($request);
            $dashboards[] = $dashboard->getTransferData();
        }

        return new JsonResponse($dashboards);
    }

    public function addDashboard(ServerRequestInterface $request): ResponseInterface
    {
        $presetIdentifier = (string)($request->getParsedBody()['preset'] ?? '');
        $dashboardPreset = $this->dashboardPresetRegistry->getDashboardPresets()[$presetIdentifier] ?? null;
        if (!$dashboardPreset instanceof DashboardPreset) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid dashboard preset!',
            ]);
        }

        $dashboardEntity = $this->dashboardRepository->create(
            $dashboardPreset,
            (int)$this->getBackendUser()->user['uid'],
            (string)($request->getParsedBody()['title'] ?? '')
        );

        $dashboardEntity->initializeWidgets($request);

        return new JsonResponse([
            'status' => 'ok',
            'dashboard' => $dashboardEntity->getTransferData(),
        ]);
    }

    public function editDashboard(ServerRequestInterface $request): ResponseInterface
    {
        $dashboardIdentifier = (string)($request->getParsedBody()['identifier'] ?? '');
        $availableDashboards = $this->dashboardRepository->getDashboardsForUser($this->getBackendUser()->getUserId());
        $dashboardEntity = $this->dashboardRepository->getDashboardByIdentifier($dashboardIdentifier);

        if (!in_array($dashboardEntity, $availableDashboards)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Dashboard is not available!',
            ]);
        }

        $this->dashboardRepository->updateDashboardSettings(
            $dashboardIdentifier,
            [
                'title' => (string)($request->getParsedBody()['title'] ?? ''),
            ]
        );

        // Fetch updated Dashboard
        $dashboardEntity = $this->dashboardRepository->getDashboardByIdentifier($dashboardIdentifier);
        $dashboardEntity->initializeWidgets($request);

        return new JsonResponse([
            'status' => 'ok',
            'dashboard' => $dashboardEntity->getTransferData(),
        ]);
    }

    public function updateDashboard(ServerRequestInterface $request): ResponseInterface
    {
        $dashboardIdentifier = (string)($request->getParsedBody()['identifier'] ?? '');
        $availableDashboards = $this->dashboardRepository->getDashboardsForUser($this->getBackendUser()->getUserId());
        $dashboardEntity = $this->dashboardRepository->getDashboardByIdentifier($dashboardIdentifier);

        if (!in_array($dashboardEntity, $availableDashboards)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Dashboard is not available!',
            ]);
        }

        $widgets = $request->getParsedBody()['widgets'] ?? [];
        $data = [];
        foreach ($widgets as $widget) {
            $data[$widget['identifier']] = [
                'identifier' => $widget['type'],
            ];
        }

        // positions
        $widgetPositions = $request->getParsedBody()['widgetPositions'] ?? [];
        foreach ($widgetPositions as $columnCount => $widgets) {
            foreach ($widgets as $widget) {
                if (!isset($widget['identifier']) || !isset($widget['height']) || !isset($widget['width']) || !isset($widget['x']) || !isset($widget['y'])) {
                    return new JsonResponse([
                        'status' => 'error',
                        'message' => 'Invalid widget positions!',
                    ]);
                }
                $identifier = $widget['identifier'] ?? '';
                unset($widget['identifier']);
                $data[$identifier]['positions'][$columnCount] = array_map('intval', $widget);
            }
        }

        // settings
        $dashboardEntity->initializeWidgets($request);
        foreach ($widgets as $widget) {
            $dashboardWidget = $dashboardEntity->getWidget($widget['identifier']);
            if ($dashboardWidget) {
                $data[$widget['identifier']]['settings'] = $dashboardWidget->getRawConfig()['settings'] ?? [];
            }
        }

        $this->dashboardRepository->updateWidgetConfig($dashboardEntity, $data);

        // Fetch updated Dashboard
        $dashboardEntity = $this->dashboardRepository->getDashboardByIdentifier($dashboardIdentifier);
        $dashboardEntity->initializeWidgets($request);

        return new JsonResponse([
            'status' => 'ok',
            'dashboard' => $dashboardEntity->getTransferData(),
        ]);
    }

    public function deleteDashboard(ServerRequestInterface $request): ResponseInterface
    {
        $dashboardIdentifier = (string)($request->getParsedBody()['identifier'] ?? '');
        $availableDashboards = $this->dashboardRepository->getDashboardsForUser($this->getBackendUser()->getUserId());
        $dashboard = $this->dashboardRepository->getDashboardByIdentifier($dashboardIdentifier);

        if (!in_array($dashboard, $availableDashboards)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Dashboard is not available!',
            ]);
        }

        $this->dashboardRepository->delete($dashboard);
        return new JsonResponse([
            'status' => 'ok',
        ]);
    }

    public function getPresets(ServerRequestInterface $request): ResponseInterface
    {
        $presets = $this->dashboardPresetRegistry->getDashboardPresets();
        return new JsonResponse($presets);
    }

    public function getCategories(ServerRequestInterface $request): ResponseInterface
    {
        $widgetGroups = $this->widgetGroupInitializationService->buildWidgetGroupsConfiguration();
        return new JsonResponse($widgetGroups);
    }

    public function getWidget(ServerRequestInterface $request): ResponseInterface
    {
        $widgetIdentifier = (string)($request->getQueryParams()['widget'] ?? '');
        $availableDashboards = $this->dashboardRepository->getDashboardsForUser($this->getBackendUser()->getUserId());
        $widgets = [];

        foreach ($availableDashboards as $dashboard) {
            $dashboard->initializeWidgets($request);
            foreach ($dashboard->getWidgets() as $dashboardEntry) {
                $widgets[$dashboardEntry->getIdentifier()] = $dashboardEntry;
            }
        }

        $dashboardWidget = $widgets[$widgetIdentifier] ?? null;
        if ($dashboardWidget === null) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Widget does not exist!',
            ]);
        }

        return new JsonResponse([
            'status' => 'ok',
            'widget' => $dashboardWidget->getTransferWidgetData()->jsonSerialize(),
        ]);
    }

    public function getWidgetSettings(ServerRequestInterface $request): ResponseInterface
    {
        $widgetIdentifier = (string)($request->getQueryParams()['widget'] ?? '');
        $availableDashboards = $this->dashboardRepository->getDashboardsForUser($this->getBackendUser()->getUserId());
        $widgets = [];

        foreach ($availableDashboards as $dashboard) {
            $dashboard->initializeWidgets($request);
            foreach ($dashboard->getWidgets() as $dashboardEntry) {
                $widgets[$dashboardEntry->getIdentifier()] = $dashboardEntry;
            }
        }

        $dashboardWidget = $widgets[$widgetIdentifier] ?? null;
        if ($dashboardWidget === null) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Widget does not exist!',
            ]);
        }

        $categories = [
            new Category(
                key: $dashboardWidget->getType(),
                label: $this->getLanguageService()->sl($dashboardWidget->getTitle()),
                description: $this->getLanguageService()->sl($dashboardWidget->getDescription()),
                icon: $dashboardWidget->getIconIdentifier(),
                settings: array_map(
                    fn(SettingDefinition $definition): EditableSetting => new EditableSetting(
                        definition: $this->resolveSettingLabels($definition),
                        value: $dashboardWidget->getSettings()->get($definition->key),
                        systemDefault: $definition->default,
                        typeImplementation: $this->settingsTypeRegistry->get($definition->type)->getJavaScriptModule(),
                    ),
                    array_values(array_filter($dashboardWidget->getSettingsDefinitions(), fn(SettingDefinition $settingDefinition) => !$settingDefinition->readonly))
                ),
            ),
        ];

        return new JsonResponse([
            'status' => 'ok',
            'categories' => json_encode($categories),
        ]);
    }

    public function updateWidgetSettings(ServerRequestInterface $request): ResponseInterface
    {
        // Check if identifier is available
        $widgetIdentifier = trim((string)($request->getParsedBody()['widget'] ?? ''));
        if ($widgetIdentifier === '') {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Widget is not available!',
            ]);
        }

        // Check for widget
        $availableDashboards = $this->dashboardRepository->getDashboardsForUser($this->getBackendUser()->getUserId());
        $targetDashboard = null;
        $targetWidget = null;
        foreach ($availableDashboards as $dashboard) {
            $dashboard->initializeWidgets($request);
            if ($dashboard->getWidget($widgetIdentifier)) {
                $targetDashboard = $dashboard;
                $targetWidget = $dashboard->getWidget($widgetIdentifier);
                break;
            }
        }

        if ($targetWidget === null) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Widget does not exist!',
            ]);
        }
        if ($targetDashboard === null) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Dashboard does not exist!',
            ]);
        }

        $rawSettings = $request->getParsedBody()['settings'] ?? [];
        $widgetData = [];
        foreach ($targetDashboard->getWidgets() as $widget) {
            $widgetData[$widget->getIdentifier()] = $widget->getRawConfig();
            if ($targetWidget->getIdentifier() === $widget->getIdentifier()) {

                $currentSettings = $widget->getRawConfig()['settings'] ?? [];
                $newSettings = $this->widgetSettingsFactory->createSettingsFromFormData($rawSettings, $widget->getSettingsDefinitions());
                $defaultSettings = $this->widgetSettingsFactory->createSettings($widget->getType(), [], $widget->getSettingsDefinitions());
                $diff = SettingsDiff::create(
                    $currentSettings,
                    $newSettings,
                    $defaultSettings,
                );
                if ($diff->changes === [] && $diff->deletions === []) {
                    return new JsonResponse([
                        'status' => 'info',
                        'message' => $this->getLanguageService()->sL('LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widget.settings.unchanged'),
                    ]);
                }
                $widgetData[$widget->getIdentifier()]['settings'] = $diff->settings;
            }
        }

        $this->dashboardRepository->updateWidgetConfig($targetDashboard, $widgetData);

        // Fetch updated Dashboard
        $dashboardEntity = $this->dashboardRepository->getDashboardByIdentifier($targetDashboard->getIdentifier());
        $dashboardEntity->initializeWidgets($request);

        $returnWidget = $dashboardEntity->getWidget($targetWidget->getIdentifier());
        if ($returnWidget === null) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Widget does not exist!',
            ]);
        }

        return new JsonResponse([
            'status' => 'ok',
        ]);
    }

    public function addWidget(ServerRequestInterface $request): ResponseInterface
    {
        $dashboardIdentifier = (string)($request->getParsedBody()['dashboard'] ?? '');
        $availableDashboards = $this->dashboardRepository->getDashboardsForUser($this->getBackendUser()->getUserId());
        $dashboard = $this->dashboardRepository->getDashboardByIdentifier($dashboardIdentifier);

        if (!in_array($dashboard, $availableDashboards)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Dashboard is not available!',
            ]);
        }

        $widgetType = (string)($request->getParsedBody()['type'] ?? '');
        if ($widgetType === '') {
            throw new \InvalidArgumentException('Argument "widget" not set.', 1714987384);
        }
        $widgets = $dashboard->getWidgetConfig();
        $widgetIdentifier = sha1($widgetType . '-' . time());
        $widgets[$widgetIdentifier] = ['identifier' => $widgetType];
        $this->dashboardRepository->updateWidgetConfig($dashboard, $widgets);

        // Fetch updated Dashboard
        $dashboard = $this->dashboardRepository->getDashboardByIdentifier($dashboardIdentifier);
        $dashboard->initializeWidgets($request);

        $widgets = [];
        foreach ($dashboard->getWidgets() as $dashboardEntry) {
            $widgets[$dashboardEntry->getIdentifier()] = $dashboardEntry;
        }

        $dashboardWidget = $widgets[$widgetIdentifier] ?? null;
        if ($dashboardWidget === null) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Widget is not available!',
            ]);
        }

        return new JsonResponse([
            'status' => 'ok',
            'widget' => $dashboardWidget->getTransferWidgetData()->jsonSerialize(),
        ]);
    }

    public function removeWidget(ServerRequestInterface $request): ResponseInterface
    {
        $dashboardIdentifier = (string)($request->getParsedBody()['dashboard'] ?? '');
        $availableDashboards = $this->dashboardRepository->getDashboardsForUser($this->getBackendUser()->getUserId());
        $dashboard = $this->dashboardRepository->getDashboardByIdentifier($dashboardIdentifier);

        if (!in_array($dashboard, $availableDashboards)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Dashboard is not available!',
            ]);
        }

        $widgetIdentifier = (string)($request->getParsedBody()['identifier'] ?? '');
        $widgets = $dashboard->getWidgetConfig();
        if ($widgetIdentifier === '' || !array_key_exists($widgetIdentifier, $widgets)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Widget is not available!',
            ]);
        }

        unset($widgets[$widgetIdentifier]);
        $this->dashboardRepository->updateWidgetConfig($dashboard, $widgets);

        return new JsonResponse([
            'status' => 'ok',
        ]);
    }

    private function resolveSettingLabels(SettingDefinition $definition): SettingDefinition
    {
        $languageService = $this->getLanguageService();
        return new SettingDefinition(...[
            ...get_object_vars($definition),
            'label' => $languageService->sL($definition->label),
            'description' => $definition->description !== null ? $languageService->sL($definition->description) : null,
            'enum' => array_map(static fn(string $label): string => $languageService->sL($label), $definition->enum),
        ]);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
