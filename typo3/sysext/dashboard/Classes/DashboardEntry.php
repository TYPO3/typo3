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
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Settings\SettingsInterface;
use TYPO3\CMS\Dashboard\Dto\WidgetConfiguration as TransferWidgetConfiguration;
use TYPO3\CMS\Dashboard\Dto\WidgetData as TransferWidgetData;
use TYPO3\CMS\Dashboard\Widgets\EventDataInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetContext;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetResult;

/**
 * Dashboard entry representing a widget instance within a dashboard.
 *
 * This class encapsulates a dashboard widget instance, providing access to its context,
 * configuration, settings, and rendering capabilities. It serves as a bridge between
 * the dashboard system and individual widget implementations, handling both legacy
 * WidgetInterface and new WidgetRendererInterface widgets.
 *
 * Each dashboard entry maintains its own widget context with instance-specific settings
 * and provides methods for rendering and configuration management.
 *
 * @internal
 */
final readonly class DashboardEntry
{
    public function __construct(
        protected WidgetContext $context,
        protected WidgetRendererInterface|WidgetInterface $renderer,
    ) {}

    public function getIdentifier(): string
    {
        return $this->context->identifier;
    }

    public function getType(): string
    {
        return $this->context->configuration->getIdentifier();
    }

    public function getTitle(): string
    {
        return $this->context->configuration->getTitle();
    }

    public function getDescription(): string
    {
        return $this->context->configuration->getDescription();
    }

    public function getIconIdentifier(): string
    {
        return $this->context->configuration->getIconIdentifier();
    }

    public function getHeight(): string
    {
        return $this->context->configuration->getHeight();
    }

    public function getWidth(): string
    {
        return $this->context->configuration->getWidth();
    }

    public function getSettings(): SettingsInterface
    {
        return $this->context->settings;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->context->request;
    }

    public function getEventData(): array
    {
        return ($this->renderer instanceof EventDataInterface) ? $this->renderer->getEventData() : [];
    }

    public function getTransferWidgetConfiguration(): TransferWidgetConfiguration
    {
        return new TransferWidgetConfiguration(
            identifier: $this->getIdentifier(),
            type: $this->getType(),
            height: $this->getHeight(),
            width: $this->getWidth(),
        );
    }

    public function getTransferWidgetData(): TransferWidgetData
    {
        $result = $this->render();
        return new TransferWidgetData(
            identifier: $this->getIdentifier(),
            type: $this->getType(),
            height: $this->getHeight(),
            width: $this->getWidth(),
            label: $result->label ?? $this->getLanguageService()->sL($this->getTitle()),
            content: $result->content,
            eventdata: $this->getEventData(),
            refreshable: $result->refreshable,
            configurable: (
                $this->renderer instanceof WidgetRendererInterface &&
                array_filter($this->renderer->getSettingsDefinitions(), fn($definition) => !$definition->readonly) !== []
            )
        );
    }

    /**
     * @return SettingDefinition[]
     */
    public function getSettingsDefinitions(): array
    {
        if ($this->renderer instanceof WidgetRendererInterface) {
            return $this->renderer->getSettingsDefinitions();
        }

        return [];
    }

    public function getRawConfig(): array
    {
        return $this->context->rawData;
    }

    protected function render(): WidgetResult
    {
        try {
            if ($this->renderer instanceof WidgetRendererInterface) {
                return $this->renderer->renderWidget($this->context);
            }
            // Map legacy WidgetInterface to "new" WidgetResult
            return new WidgetResult(
                content: $this->renderer->renderWidgetContent(),
                refreshable: $this->renderer->getOptions()['refreshAvailable'] ?? false,
            );

        } catch (\Exception) {
            return new WidgetResult(
                content: '<div class="widget-content-main">ERROR</div>',
                refreshable: true,
            );
        }
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
