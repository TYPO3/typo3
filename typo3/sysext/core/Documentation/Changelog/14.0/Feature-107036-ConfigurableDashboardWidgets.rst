..  include:: /Includes.rst.txt

..  _feature-107036-1738837673:

=================================================
Feature: #107036 - Configurable dashboard widgets
=================================================

See :issue:`107036`

Description
===========

Dashboard widgets can now be configured on a per-instance level using the
Settings API. This allows widget authors to define settings that editors can
modify directly from the dashboard interface, making widgets more flexible and
adaptable to different use cases.

Typical configuration options include URLs for RSS feeds, limits on displayed
items, or categories for filtering content.

Each widget instance maintains its own configuration, enabling multiple
instances of the same widget type with different settings on the same or
different dashboards.

Impact
======

**For editors**

*   Dashboard widgets now display a settings (cog) icon when configuration is supported.
*   Clicking the icon opens a modal dialog with configurable options.
*   Settings are applied immediately after saving, and the widget refreshes automatically.
*   Each widget instance is configured independently per user.

**For integrators**

*   Dashboard widgets can now expose user-configurable options.
*   Settings are validated using the existing Settings API type system.
*   Widget instances maintain independent configurations, allowing flexible layouts.
*   No extra configuration is required—configurable widgets automatically show the settings icon.

**For widget authors**

*   Widgets can migrate from :php-short:`\TYPO3\CMS\Dashboard\Widgets\WidgetInterface`
    to :php-short:`\TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface` to define
    settings and use configured values during rendering.
*   Settings are validated and processed automatically via the Settings API.
*   The widget context provides a :php-short:`\TYPO3\CMS\Core\Settings\Settings`
    object containing the current configuration.
*   All existing Settings API types are supported (string, int, bool, url, etc.).

Currently configurable widgets
------------------------------

The following core widgets now support configuration:

**RSS widget**
  * **Label** – custom title for the widget instance
  * **Feed URL** – RSS feed URL to display (supports URL validation)
  * **Limit** – number of RSS items to show (default: 5)
  * **Lifetime** – cache duration in seconds for the RSS feed

**Pages with internal note widget**
  * **Category** – filter notes by category (All, Default, Instructions, Template, Notes, Todo)
  * Includes an upgrade wizard to migrate existing widgets of this type to the new format.

Example
-------

Widget authors can implement configurable widgets by migrating from the
current interface :php-short:`\TYPO3\CMS\Dashboard\Widgets\WidgetInterface` to
the new renderer interface :php-short:`\TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface`,
which allows defining settings in the widget renderer:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Widgets/ConfigurableWidget.php

    <?php

    use TYPO3\CMS\Core\Settings\SettingDefinition;
    use TYPO3\CMS\Dashboard\Widgets\WidgetContext;
    use TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface;
    use TYPO3\CMS\Dashboard\Widgets\WidgetResult;

    class ConfigurableWidget implements WidgetRendererInterface
    {
        public function getSettingsDefinitions(): array
        {
            return [
                new SettingDefinition(
                    key: 'title',
                    type: 'string',
                    default: 'Default Title',
                    label: 'my_extension.my_widget:settings.label',
                    description: 'my_extension.my_widget:settings.description.label',
                ),
                new SettingDefinition(
                    key: 'limit',
                    type: 'int',
                    default: 10,
                    label: 'my_extension.my_widget:settings.limit',
                    description: 'my_extension.my_widget:settings.description.limit',
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

Editors can configure these widgets directly in the dashboard interface:

1.  Navigate to the dashboard containing the widget.
2.  Click the settings (cog) icon on the widget.
3.  Modify the available settings in the modal dialog.
4.  Click *Save* to apply the changes.

The widget automatically refreshes with the updated configuration.

..  index:: Backend, ext:dashboard
