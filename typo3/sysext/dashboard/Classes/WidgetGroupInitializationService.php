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

use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * @internal
 */
class WidgetGroupInitializationService
{
    public function __construct(
        private readonly WidgetGroupRegistry $widgetGroupRegistry,
        private readonly WidgetRegistry $widgetRegistry
    ) {
    }

    /**
     * Define the different groups of widgets as shown in the modal when adding a widget to the current dashboard
     */
    public function buildWidgetGroupsConfiguration(): array
    {
        $groupConfigurations = [];
        foreach ($this->widgetGroupRegistry->getWidgetGroups() as $widgetGroup) {
            $widgets = [];
            $widgetGroupIdentifier = $widgetGroup->getIdentifier();

            $widgetsForGroup = $this->widgetRegistry->getAvailableWidgetsForWidgetGroup($widgetGroupIdentifier);
            foreach ($widgetsForGroup as $widgetConfiguration) {
                $widgets[$widgetConfiguration->getIdentifier()] = [
                    'iconIdentifier' => $widgetConfiguration->getIconIdentifier(),
                    'title' => $this->getLanguageService()->sL($widgetConfiguration->getTitle()),
                    'description' => $this->getLanguageService()->sL($widgetConfiguration->getDescription()),
                ];
            }

            $groupConfigurations[$widgetGroupIdentifier] = [
                'identifier' => $widgetGroupIdentifier,
                'title' => $widgetGroup->getTitle(),
                'widgets' => $widgets,
            ];
        }

        return $groupConfigurations;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
