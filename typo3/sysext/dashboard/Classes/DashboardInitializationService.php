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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\AdditionalJavaScriptInterface;
use TYPO3\CMS\Dashboard\Widgets\RequireJsModuleInterface;

/**
 * @internal
 */
class DashboardInitializationService
{
    protected const MODULE_DATA_CURRENT_DASHBOARD_IDENTIFIER = 'dashboard/current_dashboard/';

    /**
     * @var DashboardRepository
     */
    private $dashboardRepository;

    /**
     * @var DashboardPresetRegistry
     */
    private $dashboardPresetRegistry;

    /**
     * @var Dashboard
     */
    private $currentDashboard;

    /**
     * @var BackendUserAuthentication
     */
    private $user;

    /**
     * @var array
     */
    private $requireJsModules = [];
    private $jsFiles = [];
    private $cssFiles = [];

    public function __construct(
        DashboardRepository $dashboardRepository,
        DashboardPresetRegistry $dashboardPresetRegistry
    ) {
        $this->dashboardRepository = $dashboardRepository;
        $this->dashboardPresetRegistry = $dashboardPresetRegistry;
    }

    public function initializeDashboards(BackendUserAuthentication $user): void
    {
        $this->user = $user;
        $this->currentDashboard = $this->defineCurrentDashboard();

        $this->currentDashboard->initializeWidgets();
        $this->defineResourcesOfWidgets($this->currentDashboard->getWidgets());
    }

    public function getCurrentDashboard(): Dashboard
    {
        return $this->currentDashboard;
    }

    protected function defineCurrentDashboard(): Dashboard
    {
        $currentDashboard = $this->dashboardRepository->getDashboardByIdentifier($this->loadCurrentDashboard($this->user));
        if (!$currentDashboard instanceof Dashboard) {
            $dashboards = $this->getDashboardsForUser();
            $currentDashboard = reset($dashboards);
            $this->saveCurrentDashboard($this->user, $currentDashboard->getIdentifier());
        }

        return $currentDashboard;
    }

    protected function createDefaultDashboards(): array
    {
        $dashboardsForUser = [];

        $userConfig = $this->user->getTSConfig();
        $dashboardsToCreate = GeneralUtility::trimExplode(
            ',',
            $userConfig['options.']['dashboard.']['dashboardPresetsForNewUsers'] ?? 'default'
        );

        /** @var DashboardPreset $dashboardPreset */
        foreach ($this->dashboardPresetRegistry->getDashboardPresets() as $dashboardPreset) {
            if (in_array($dashboardPreset->getIdentifier(), $dashboardsToCreate, true)) {
                $dashboard = $this->dashboardRepository->create(
                    $dashboardPreset,
                    (int)$this->user->user['uid']
                );

                if ($dashboard instanceof Dashboard) {
                    $dashboardsForUser[$dashboard->getIdentifier()] = $dashboard;
                }
            }
        }

        return $dashboardsForUser;
    }

    /**
     * @return Dashboard[]
     */
    public function getDashboardsForUser(): array
    {
        $dashboards = [];
        foreach ($this->dashboardRepository->getDashboardsForUser((int)$this->user->user['uid']) as $dashboard) {
            $dashboards[$dashboard->getIdentifier()] = $dashboard;
        }

        if ($dashboards === []) {
            $dashboards = $this->createDefaultDashboards();
        }

        return $dashboards;
    }

    /**
     * @param array $widgets
     */
    protected function defineResourcesOfWidgets(array $widgets): void
    {
        foreach ($widgets as $widget) {
            $concreteInstance = GeneralUtility::makeInstance($widget->getServiceName());
            if ($concreteInstance instanceof RequireJsModuleInterface) {
                $this->defineRequireJsModules($concreteInstance);
            }
            if ($concreteInstance instanceof AdditionalCssInterface) {
                $this->defineCssFiles($concreteInstance);
            }
            if ($concreteInstance instanceof AdditionalJavaScriptInterface) {
                $this->defineJsFiles($concreteInstance);
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
        foreach ($widgetInstance->getJsFiles() as $jsFile) {
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
     * @return array
     */
    public function getRequireJsModules(): array
    {
        return $this->requireJsModules;
    }

    /**
     * @return array
     */
    public function getJsFiles(): array
    {
        return $this->jsFiles;
    }

    /**
     * @return array
     */
    public function getCssFiles(): array
    {
        return $this->cssFiles;
    }
}
