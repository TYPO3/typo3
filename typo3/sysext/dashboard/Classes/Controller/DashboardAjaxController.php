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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Dashboard\DashboardPreset;
use TYPO3\CMS\Dashboard\DashboardPresetRegistry;
use TYPO3\CMS\Dashboard\DashboardRepository;
use TYPO3\CMS\Dashboard\Dto\Dashboard as TransferDashboard;
use TYPO3\CMS\Dashboard\Dto\WidgetConfiguration as TransferWidgetConfiguration;
use TYPO3\CMS\Dashboard\Dto\WidgetData as TransferWidgetData;
use TYPO3\CMS\Dashboard\WidgetGroupInitializationService;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use TYPO3\CMS\Dashboard\Widgets\EventDataInterface;

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
        protected readonly UriBuilder $uriBuilder,
    ) {}

    public function getDashboards(ServerRequestInterface $request): ResponseInterface
    {
        $availableDashboards = $this->dashboardRepository->getDashboardsForUser($this->getBackendUser()->getUserId());
        $dashboards = [];
        foreach ($availableDashboards as $dashboard) {
            $dashboard->initializeWidgets($request);
            $widgets = [];
            foreach ($dashboard->getWidgets() as $widgetKey => $widgetConfiguration) {
                $widgets[] = new TransferWidgetConfiguration(
                    identifier: $widgetKey,
                    type: $widgetConfiguration->getIdentifier(),
                    height: $widgetConfiguration->getHeight(),
                    width: $widgetConfiguration->getWidth(),
                );
            }
            $dashboards[] = new TransferDashboard(
                identifier: $dashboard->getIdentifier(),
                title: $dashboard->getTitle(),
                widgets: $widgets,
                widgetPositions: $dashboard->getWidgetPositions(),
            );
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
        $widgets = [];
        foreach ($dashboardEntity->getWidgets() as $widgetKey => $widgetConfiguration) {
            $widgets[] = new TransferWidgetConfiguration(
                identifier: $widgetKey,
                type: $widgetConfiguration->getIdentifier(),
                height: $widgetConfiguration->getHeight(),
                width: $widgetConfiguration->getWidth(),
            );
        }
        $dashboard = new TransferDashboard(
            identifier: $dashboardEntity->getIdentifier(),
            title: $dashboardEntity->getTitle(),
            widgets: $widgets,
            widgetPositions: $dashboardEntity->getWidgetPositions(),
        );

        return new JsonResponse([
            'status' => 'ok',
            'dashboard' => $dashboard,
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
        $widgets = [];
        foreach ($dashboardEntity->getWidgets() as $widgetKey => $widgetConfiguration) {
            $widgets[] = new TransferWidgetConfiguration(
                identifier: $widgetKey,
                type: $widgetConfiguration->getIdentifier(),
                height: $widgetConfiguration->getHeight(),
                width: $widgetConfiguration->getWidth(),
            );
        }
        $dashboard = new TransferDashboard(
            identifier: $dashboardEntity->getIdentifier(),
            title: $dashboardEntity->getTitle(),
            widgets: $widgets,
            widgetPositions: $dashboardEntity->getWidgetPositions(),
        );

        return new JsonResponse([
            'status' => 'ok',
            'dashboard' => $dashboard,
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
            $data[$widget['identifier']] = ['identifier' => $widget['type']];
        }
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
        $this->dashboardRepository->updateWidgetConfig($dashboardEntity, $data);

        // Fetch updated Dashboard
        $dashboardEntity = $this->dashboardRepository->getDashboardByIdentifier($dashboardIdentifier);
        $dashboardEntity->initializeWidgets($request);
        $widgets = [];
        foreach ($dashboardEntity->getWidgets() as $widgetKey => $widgetConfiguration) {
            $widgets[] = new TransferWidgetConfiguration(
                identifier: $widgetKey,
                type: $widgetConfiguration->getIdentifier(),
                height: $widgetConfiguration->getHeight(),
                width: $widgetConfiguration->getWidth(),
            );
        }
        $dashboard = new TransferDashboard(
            identifier: $dashboardEntity->getIdentifier(),
            title: $dashboardEntity->getTitle(),
            widgets: $widgets,
            widgetPositions: $dashboardEntity->getWidgetPositions(),
        );

        return new JsonResponse([
            'status' => 'ok',
            'dashboard' => $dashboard,
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
            foreach ($dashboard->getWidgets() as $key => $value) {
                $widgets[$key] = $value;
            }
        }

        $widgetObject = $widgets[$widgetIdentifier] ?? null;
        if ($widgetObject === null) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Widget is not available!',
            ]);
        }
        try {
            $renderWidget = $this->widgetRegistry->getAvailableWidget($request, $widgetObject->getIdentifier());
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Widget is not available!',
            ]);
        }

        $widget = new TransferWidgetData(
            identifier: $widgetIdentifier,
            type: $widgetObject->getIdentifier(),
            height: $widgetObject->getHeight(),
            width: $widgetObject->getWidth(),
            label: $this->getLanguageService()->sL($widgetObject->getTitle()),
            content: $renderWidget->renderWidgetContent(),
            options: $renderWidget->getOptions(),
            eventdata: ($renderWidget instanceof EventDataInterface) ? $renderWidget->getEventData() : [],
        );

        return new JsonResponse([
            'status' => 'ok',
            'widget' => $widget->jsonSerialize(),
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
        foreach ($dashboard->getWidgets() as $key => $value) {
            $widgets[$key] = $value;
        }

        $widgetObject = $widgets[$widgetIdentifier] ?? null;
        if ($widgetObject === null) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Widget is not available!',
            ]);
        }
        try {
            $renderWidget = $this->widgetRegistry->getAvailableWidget($request, $widgetObject->getIdentifier());
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Widget is not available!',
            ]);
        }

        $widget = new TransferWidgetData(
            identifier: $widgetIdentifier,
            type: $widgetObject->getIdentifier(),
            height: $widgetObject->getHeight(),
            width: $widgetObject->getWidth(),
            label: $this->getLanguageService()->sL($widgetObject->getTitle()),
            content: $renderWidget->renderWidgetContent(),
            options: $renderWidget->getOptions(),
            eventdata: ($renderWidget instanceof EventDataInterface) ? $renderWidget->getEventData() : [],
        );

        return new JsonResponse([
            'status' => 'ok',
            'widget' => $widget->jsonSerialize(),
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

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
