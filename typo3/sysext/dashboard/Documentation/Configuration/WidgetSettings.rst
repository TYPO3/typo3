.. include:: /Includes.rst.txt

..  _settings:

=====================================
Adjust settings of registered widgets
=====================================

..  versionadded:: 14.0
    `Configurable Dashboard Widgets <https://docs.typo3.org/permalink/changelog:feature-107036-1738837673>`_
    have been introduced with TYPO3 14.0.

..  contents:: Table of contents

..  _adjust-settings-of-widget-why:
..  _configurable-widgets:

Configurable dashboard widgets
------------------------------

..  versionadded:: 14.0

Dashboard widgets can be configured on a per-instance level using the Settings
API. This allows widget authors to define configurable settings that editors
can modify directly from the dashboard interface, making widgets more
flexible and user-friendly.

Examples are URLs for RSS feeds, limits on displayed items, or categories for
filtering content.

Each widget instance maintains its own configuration, enabling multiple
instances of the same widget type with different settings on the same or
different dashboards.

Configurable widgets display a `settings (cog) icon <https://docs.typo3.org/permalink/typo3/cms-dashboard:widgets-configuration>`_
and allow editors to configure the widget in a modal dialog.

Extension authors can implement :php-short:`\TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface`
to make their widgets configurable:
`Configurable dashboard widget implementation <https://docs.typo3.org/permalink/typo3/cms-dashboard:configurable-widget-implementation>`_.

..  _adjust-settings-of-widget:

Adjust settings of registered widgets
=====================================

Each widget is registered with an identifier, and all :file:`Services.*` files are merged.
Therefore it is possible to override widgets.
In order to override, the extension which should override has to be loaded after the extension that registered the widget.

Concrete options depend on the widget to configure.
Each widget should provide documentation covering all possible options and their meaning.
For delivered widgets by EXT:dashboard see :ref:`widgets`.

In case a widget defined by EXT:dashboard should be adjusted,
the extension has to define a dependency to EXT:dashboard.

Afterwards the widget can be registered again, with different options. See
:ref:`register-new-widget` to get an in depth example of how to register a widget.
