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
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;

/**
 * @internal
 */
class Dashboard
{
    /**
     * @var array<string,WidgetConfigurationInterface>
     */
    protected $widgets = [];

    /**
     * @var array<string,array>
     */
    protected $widgetOptions = [];

    protected ?object $widgetPositions = null;

    /**
     * @param array<string,array<string,string|array>> $widgetConfig
     */
    public function __construct(
        protected readonly string $identifier,
        protected readonly string $title,
        protected readonly array $widgetConfig,
        protected readonly WidgetRegistry $widgetRegistry,
        protected readonly ContainerInterface $container
    ) {}

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getTitle(): string
    {
        return $this->getLanguageService()->sL($this->title) ?: $this->title;
    }

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

    public function getWidgetPositions(): object
    {
        return $this->widgetPositions ?? new \stdClass();
    }

    /**
     * This will return a list of all widgets of the current dashboard object. It will only include available
     * widgets and will add the initialized object of the widget itself
     */
    public function initializeWidgets(ServerRequestInterface $request): void
    {
        $availableWidgets = $this->widgetRegistry->getAvailableWidgets();
        $this->widgetPositions = new \stdClass();
        foreach ($this->widgetConfig as $hash => $widgetConfig) {
            $widgetConfigIdentifier = $widgetConfig['identifier'] ?? '';
            if ($widgetConfigIdentifier !== '' && array_key_exists($widgetConfigIdentifier, $availableWidgets)) {
                $this->widgets[$hash] = $availableWidgets[$widgetConfigIdentifier];

                $widgetObject = $this->widgetRegistry->getAvailableWidget($request, $widgetConfigIdentifier);
                $this->widgetOptions[$hash] = $widgetObject->getOptions();

                $positions = $widgetConfig['positions'] ?? [];
                foreach ($positions as $columnCount => $position) {
                    if (!isset($position['height']) || !isset($position['width']) || !isset($position['x']) || !isset($position['y'])) {
                        continue;
                    }
                    if (!isset($this->widgetPositions->{$columnCount})) {
                        $this->widgetPositions->{$columnCount} = [];
                    }
                    $this->widgetPositions->{$columnCount}[] = [
                        'identifier' => $hash,
                        'height' => (int)$position['height'],
                        'width' => (int)$position['width'],
                        'x' => (int)$position['x'],
                        'y' => (int)$position['y'],
                    ];
                }
            }
        }
        foreach (array_keys(get_object_vars($this->widgetPositions)) as $columnCount) {
            usort(
                $this->widgetPositions->{$columnCount},
                static fn(array $a, array $b): int => $a['y'] !== $b['y'] ? $a['y'] - $b['y'] : $a['x'] - $b['x']
            );
        }
    }

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
