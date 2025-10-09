.. include:: /Includes.rst.txt

.. _feature-107036-1738837673:

=================================================
Feature: #107036 - Configurable Dashboard Widgets
=================================================

See :issue:`107036`

Description
===========

Dashboard widgets can now be configured on a per-instance level using the Settings API.
This allows widget authors to define configurable settings that editors can modify
directly from the dashboard interface, making widgets more flexible and user-friendly.

Examples are URLs for RSS feeds, limits on displayed items, or
categories for filtering content.

Each widget instance maintains its own configuration, enabling multiple instances
of the same widget type with different settings on the same or different dashboards.

Impact
======

**For Editors:**

* Dashboard widgets now display a settings (cog) icon when they support configuration
* Clicking the settings icon opens a modal dialog with configurable options
* Settings are applied immediately after saving, with the widget content refreshing automatically
* Each widget can be configured independently per user / per instance

**For Integrators:**

* Dashboard widgets can now provide user-configurable options
* Settings are validated using the existing Settings API type system
* Widget instances maintain separate configurations, allowing flexible dashboard layouts
* No additional configuration is required - configurable widgets automatically show the settings icon

**For Widget Authors:**

* Widgets can migrate from :php-short:`TYPO3\CMS\Dashboard\Widgets\WidgetInterface` to
  :php-short:`TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface` to provide settings definitions
  and use configured settings during widget rendering.
* Settings are automatically validated and processed using the Settings API
* Widget context includes a :php:`Settings` object with current configuration values
* Settings definitions support all existing Settings API types (string, int, bool, url, etc.)

Currently Configurable Widgets
------------------------------

The following core widgets now support configuration:

**RSS Widget**
  * **Label**: Custom title for the widget instance
  * **Feed URL**: RSS feed URL to display (supports URL validation)
  * **Limit**: Number of RSS items to show (default: 5)
  * **Lifetime**: Cache duration in seconds for the RSS feed

**Pages with Internal Note Widget**
  * **Category**: Filter notes by category (All, Default, Instructions, Template, Notes, Todo)
  * An upgrade wizard exists to migrate existing widgets of this type to the new format.

Example
-------

Widget authors can implement configurable widgets by migrating from the current
interface :php:`TYPO3\CMS\Dashboard\Widgets\WidgetInterface` to the new
renderer interface :php:`TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface`
which allows to defining settings in their widget renderer:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Widgets/ConfigurableWidget.php

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
                label: $title,
                content: '<!-- widget content -->',
                refreshable: true
            );
        }
    }

Editors can configure these widgets through the dashboard interface:

1. Navigate to the dashboard containing the widget
2. Click the settings (cog) icon on the widget
3. Modify the available settings in the modal dialog
4. Click "Save" to apply changes

The widget will automatically refresh with the new configuration.

.. index:: Backend, ext:dashboard
