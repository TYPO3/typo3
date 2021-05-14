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
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;

/**
 * @internal
 */
class Dashboard
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var array<string,array<string,string>>
     */
    protected $widgetConfig;

    /**
     * @var WidgetRegistry
     */
    protected $widgetRegistry;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array<string,WidgetConfigurationInterface>
     */
    protected $widgets = [];

    /**
     * @var array<string,array>
     */
    protected $widgetOptions = [];

    public function __construct(
        string $identifier,
        string $title,
        array $widgetConfig,
        WidgetRegistry $widgetRegistry,
        ContainerInterface $container
    ) {
        $this->identifier = $identifier;
        $this->title = $title;
        $this->widgetConfig = $widgetConfig;
        $this->widgetRegistry = $widgetRegistry;
        $this->container = $container;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->getLanguageService()->sL($this->title) ?: $this->title;
    }

    /**
     * @return array
     */
    public function getWidgetConfig(): array
    {
        return $this->widgetConfig;
    }

    /**
     * @return array<string,WidgetConfigurationInterface>
     */
    public function getWidgets(): array
    {
        return $this->widgets;
    }

    /**
     * This will return a list of all widgets of the current dashboard object. It will only include available
     * widgets and will add the initialized object of the widget itself
     */
    public function initializeWidgets(): void
    {
        $availableWidgets = $this->widgetRegistry->getAvailableWidgets();
        foreach ($this->widgetConfig as $hash => $widgetConfig) {
            if (array_key_exists($widgetConfig['identifier'], $availableWidgets)) {
                $this->widgets[$hash] = $availableWidgets[$widgetConfig['identifier']];

                $widgetObject = $this->widgetRegistry->getAvailableWidget($widgetConfig['identifier']);
                if (method_exists($widgetObject, 'getOptions')) {
                    $this->widgetOptions[$hash] = $widgetObject->getOptions();
                }
            }
        }
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return array<string,array>
     */
    public function getWidgetOptions(): array
    {
        return $this->widgetOptions;
    }
}
