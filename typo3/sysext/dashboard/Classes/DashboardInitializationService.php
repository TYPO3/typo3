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

namespace TYPO3\CMS\Dashboard;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\AdditionalJavaScriptInterface;
use TYPO3\CMS\Dashboard\Widgets\JavaScriptInterface;

/**
 * @internal
 */
class DashboardInitializationService
{
    protected const MODULE_DATA_CURRENT_DASHBOARD_IDENTIFIER = 'dashboard/current_dashboard/';

    /**
     * @var list<JavaScriptModuleInstruction>
     */
    protected array $javaScriptModuleInstructions = [];
    private array $jsFiles = [];
    private array $cssFiles = [];

    public function __construct(
        private readonly DashboardRepository $dashboardRepository,
        private readonly DashboardPresetRegistry $dashboardPresetRegistry,
        private readonly WidgetRegistry $widgetRegistry,
    ) {}

    public function initializeDashboards(ServerRequestInterface $request, BackendUserAuthentication $user): void
    {
        $this->defineCurrentDashboard($user);
        $this->defineResourcesOfWidgets();
    }

    protected function defineCurrentDashboard(BackendUserAuthentication $user): Dashboard
    {
        $currentDashboard = $this->dashboardRepository->getDashboardByIdentifier($this->loadCurrentDashboard($user));
        if ($currentDashboard === null) {
            $dashboards = $this->getDashboardsForUser($user);
            /** @var Dashboard $currentDashboard */
            $currentDashboard = reset($dashboards);
            $this->saveCurrentDashboard($user, $currentDashboard->getIdentifier());
        }

        return $currentDashboard;
    }

    protected function createDefaultDashboards(BackendUserAuthentication $user): array
    {
        $dashboardsForUser = [];

        $userConfig = $user->getTSConfig();
        $dashboardsToCreate = GeneralUtility::trimExplode(
            ',',
            $userConfig['options.']['dashboard.']['dashboardPresetsForNewUsers'] ?? 'default'
        );

        foreach ($this->dashboardPresetRegistry->getDashboardPresets() as $dashboardPreset) {
            if (in_array($dashboardPreset->getIdentifier(), $dashboardsToCreate, true)) {
                $dashboard = $this->dashboardRepository->create(
                    $dashboardPreset,
                    (int)$user->user['uid']
                );

                if ($dashboard === null) {
                    continue;
                }
                $dashboardsForUser[$dashboard->getIdentifier()] = $dashboard;
            }
        }

        return $dashboardsForUser;
    }

    /**
     * @return Dashboard[]
     */
    public function getDashboardsForUser(BackendUserAuthentication $user): array
    {
        $dashboards = [];
        foreach ($this->dashboardRepository->getDashboardsForUser((int)$user->user['uid']) as $dashboard) {
            $dashboards[$dashboard->getIdentifier()] = $dashboard;
        }

        if ($dashboards === []) {
            $dashboards = $this->createDefaultDashboards($user);
        }

        return $dashboards;
    }

    protected function defineResourcesOfWidgets(): void
    {
        foreach ($this->widgetRegistry->getAvailableWidgets() as $widget) {
            $concreteInstance = GeneralUtility::makeInstance($widget->getServiceName());
            if ($concreteInstance instanceof JavaScriptInterface) {
                $this->defineJavaScriptInstructions($concreteInstance);
            }
            if ($concreteInstance instanceof AdditionalCssInterface) {
                $this->defineCssFiles($concreteInstance);
            }
            if ($concreteInstance instanceof AdditionalJavaScriptInterface) {
                $this->defineJsFiles($concreteInstance);
            }
        }
    }

    protected function defineJavaScriptInstructions(JavaScriptInterface $widgetInstance): void
    {
        foreach ($widgetInstance->getJavaScriptModuleInstructions() as $instruction) {
            $this->javaScriptModuleInstructions[] = $instruction;
        }
    }

    /**
     * Define the correct path of the JS files of a widget and add them to the list of JS files that needs to be
     * included
     */
    protected function defineJsFiles(AdditionalJavaScriptInterface $widgetInstance): void
    {
        foreach ($widgetInstance->getJsFiles() as $jsFile) {
            if (PathUtility::isExtensionPath($jsFile)) {
                $jsFile = PathUtility::getPublicResourceWebPath($jsFile);
            }
            $this->jsFiles[$jsFile] = $jsFile;
        }
    }

    /**
     * Define the correct path of the CSS files of a widget and add them to the list of CSS files that needs to be
     * included
     */
    protected function defineCssFiles(AdditionalCssInterface $widgetInstance): void
    {
        foreach ($widgetInstance->getCssFiles() as $cssFile) {
            if (PathUtility::isExtensionPath($cssFile)) {
                $cssFile = PathUtility::getPublicResourceWebPath($cssFile);
            }
            $this->cssFiles[$cssFile] = $cssFile;
        }
    }

    protected function loadCurrentDashboard(BackendUserAuthentication $user): string
    {
        return $user->getModuleData(self::MODULE_DATA_CURRENT_DASHBOARD_IDENTIFIER) ?? '';
    }

    protected function saveCurrentDashboard(BackendUserAuthentication $user, string $identifier): void
    {
        $user->pushModuleData(self::MODULE_DATA_CURRENT_DASHBOARD_IDENTIFIER, $identifier);
    }

    /**
     * @return list<JavaScriptModuleInstruction>
     */
    public function getJavaScriptModuleInstructions(): array
    {
        return $this->javaScriptModuleInstructions;
    }

    public function getJsFiles(): array
    {
        return $this->jsFiles;
    }

    public function getCssFiles(): array
    {
        return $this->cssFiles;
    }
}
