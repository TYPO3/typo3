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
use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Dashboard\Dto\Dashboard as TransferDashboard;
use TYPO3\CMS\Dashboard\Dto\WidgetConfiguration as TransferWidgetConfiguration;
use TYPO3\CMS\Dashboard\Factory\WidgetSettingsFactory;
use TYPO3\CMS\Dashboard\Widgets\WidgetContext;
use TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface;

/**
 * @internal
 */
class Dashboard
{
    /**
     * @var array<string,DashboardEntry>
     */
    protected array $widgets = [];

    protected ?object $widgetPositions = null;

    /**
     * @param array<string,array<string,string|array>> $widgetConfig
     */
    public function __construct(
        protected readonly string $identifier,
        protected readonly string $title,
        protected readonly array $widgetConfig,
        protected readonly WidgetRegistry $widgetRegistry,
        protected readonly WidgetSettingsFactory $widgetSettingsFactory,
        protected readonly ContainerInterface $container,
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
     * @return array<string,DashboardEntry>
     */
    public function getWidgets(): array
    {
        return $this->widgets;
    }

    public function getWidget(string $identifier): ?DashboardEntry
    {
        return $this->widgets[$identifier] ?? null;
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

                // Widget (Renderer) Instance
                $widgetRenderer = $this->widgetRegistry->getAvailableWidget($request, $widgetConfigIdentifier);

                // Dashboard Entry with Widget Context
                $this->widgets[$hash] = new DashboardEntry(
                    context: new WidgetContext(
                        identifier: $hash,
                        rawData: $widgetConfig,
                        configuration: $availableWidgets[$widgetConfigIdentifier],
                        settings: $widgetRenderer instanceof WidgetRendererInterface ? $this->widgetSettingsFactory->createSettings(
                            $widgetConfigIdentifier,
                            $widgetConfig['settings'] ?? [],
                            $widgetRenderer->getSettingsDefinitions(),
                        ) : new Settings([]),
                        request: $request,
                    ),
                    renderer: $widgetRenderer,
                );

                // Widget Positions
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

    public function getTransferData(): TransferDashboard
    {
        return new TransferDashboard(
            identifier: $this->getIdentifier(),
            title: $this->getTitle(),
            widgets: array_values(array_map(fn(DashboardEntry $entry): TransferWidgetConfiguration => $entry->getTransferWidgetConfiguration(), $this->getWidgets())),
            widgetPositions: $this->getWidgetPositions(),
        );
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
