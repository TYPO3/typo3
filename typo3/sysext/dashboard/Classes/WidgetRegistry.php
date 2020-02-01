<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Dashboard;

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

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Dashboard\Widgets\Interfaces\WidgetInterface;

class WidgetRegistry implements SingletonInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var WidgetInterface[]
     */
    private $widgets = [];

    /**
     * @var array
     */
    private $widgetsPerWidgetGroup = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getAvailableWidgets(): array
    {
        return $this->checkPermissionOfWidgets($this->widgets);
    }

    public function getAllWidgets(): array
    {
        return $this->widgets;
    }

    public function getAvailableWidgetsForWidgetGroup(string $widgetGroupIdentifier): array
    {
        if (!array_key_exists($widgetGroupIdentifier, $this->widgetsPerWidgetGroup)) {
            return [];
        }
        return $this->checkPermissionOfWidgets($this->widgetsPerWidgetGroup[$widgetGroupIdentifier]);
    }

    public function registerWidget(string $identifier, string $widgetServiceName, array $widgetGroupIdentifiers): void
    {
        $this->widgets[$identifier] = $widgetServiceName;
        foreach ($widgetGroupIdentifiers as $widgetGroupIdentifier) {
            $this->widgetsPerWidgetGroup[$widgetGroupIdentifier][$identifier] = $widgetServiceName;
        }
    }

    protected function checkPermissionOfWidgets(array $widgets): array
    {
        return array_filter($widgets, function ($widgetIdentifier) {
            return $this->getBackendUser()->check('available_widgets', $widgetIdentifier);
        }, ARRAY_FILTER_USE_KEY);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    public function widgetItemsProcFunc(array $parameters)
    {
        foreach ($this->widgets as $identifier => $widget) {
            $widgetObject = $this->container->get($widget);
            $parameters['items'][] = [
                $widgetObject->getTitle() ,
                $identifier,
                $widgetObject->getIconIdentifier() ?? 'dashboard-default',
                $widgetObject->getDescription()
            ];
        }
    }
}
