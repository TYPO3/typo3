<?php

use TYPO3\CMS\Dashboard\Widgets\WidgetContext;
use TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetResult;
use TYPO3\CMS\Core\Settings\SettingDefinition;

class ConfigurableWidget implements WidgetRendererInterface
{
    public function getSettingsDefinitions(): array
    {
        return [
            new SettingDefinition(
                key: 'title',
                type: 'string',
                default: 'Default Title',
                label: 'LLL:EXT:my_extension/Resources/Private/Language/locallang_my_widget.xlf:settings.label',
                description: 'LLL:EXT:my_extension/Resources/Private/Language/locallang_my_widget.xlf:settings.description.label',
            ),
            new SettingDefinition(
                key: 'limit',
                type: 'int',
                default: 10,
                label: 'LLL:EXT:my_extension/Resources/Private/Language/locallang_my_widget.xlf:settings.limit',
                description: 'LLL:EXT:my_extension/Resources/Private/Language/locallang_my_widget.xlf:settings.description.limit',
            ),
        ];
    }

    public function renderWidget(WidgetContext $context): WidgetResult
    {
        $settings = $context->settings;
        $title = $settings->get('title');
        $limit = $settings->get('limit');

        // Use settings to customize widget output
        return new WidgetResult(
            content: '<!-- widget content -->',
            label: $title,
            refreshable: true
        );
    }
}
