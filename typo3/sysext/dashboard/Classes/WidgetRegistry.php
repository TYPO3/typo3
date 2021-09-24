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

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

/**
 * @internal
 */
class WidgetRegistry implements SingletonInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array<string,WidgetConfigurationInterface>
     */
    private $widgets = [];

    /**
     * @var array<string, WidgetConfigurationInterface[]>
     */
    private $widgetsPerWidgetGroup = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return WidgetConfigurationInterface[]
     */
    public function getAvailableWidgets(): array
    {
        return $this->checkPermissionOfWidgets($this->widgets);
    }

    /**
     * @return WidgetConfigurationInterface[]
     */
    public function getAllWidgets(): array
    {
        return $this->widgets;
    }

    /**
     * @throws \InvalidArgumentException If requested identifier does not exist.
     */
    public function getAvailableWidget(string $identifier): WidgetInterface
    {
        if (array_key_exists($identifier, $this->getAvailableWidgets())) {
            return $this->container->get($this->widgets[$identifier]->getServiceName());
        }

        throw new \InvalidArgumentException('Requested widget "' . $identifier . '" does not exist.', 1584777201);
    }

    /**
     * @param string $widgetGroupIdentifier
     * @return WidgetConfigurationInterface[]
     */
    public function getAvailableWidgetsForWidgetGroup(string $widgetGroupIdentifier): array
    {
        if (!array_key_exists($widgetGroupIdentifier, $this->widgetsPerWidgetGroup)) {
            return [];
        }
        return $this->checkPermissionOfWidgets($this->widgetsPerWidgetGroup[$widgetGroupIdentifier]);
    }

    public function registerWidget(string $serviceName): void
    {
        $widgetConfiguration = $this->container->get($serviceName);
        $this->widgets[$widgetConfiguration->getIdentifier()] = $widgetConfiguration;
        foreach ($widgetConfiguration->getGroupNames() as $groupIdentifier) {
            $this->widgetsPerWidgetGroup = ArrayUtility::setValueByPath(
                $this->widgetsPerWidgetGroup,
                $groupIdentifier . '/' . $widgetConfiguration->getIdentifier(),
                $widgetConfiguration
            );
        }
    }

    /**
     * @param WidgetConfigurationInterface[] $widgets
     * @return WidgetConfigurationInterface[]
     */
    protected function checkPermissionOfWidgets(array $widgets): array
    {
        return array_filter($widgets, function ($identifier) {
            return $this->getBackendUser()->check('available_widgets', $identifier);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function widgetItemsProcFunc(array &$parameters): void
    {
        foreach ($this->widgets as $widget) {
            $parameters['items'][] = [
                $widget->getTitle(),
                $widget->getIdentifier(),
                $widget->getIconIdentifier(),
                null,
                $widget->getDescription(),
            ];
        }
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
