.. include:: /Includes.rst.txt

..  _for-editors:

===========
For Editors
===========

Target group: **Editors**

Welcome to our small dashboard introduction.
We will explain the basic usage of the TYPO3 dashboard.

..  _opening-dashboard:

Opening Dashboard
=================

By default the dashboard is opened when logging into the backend.

The dashboard can be opened at any time by clicking the entry
:guilabel:`Dashboard` in the module menu.

.. figure:: /Images/DashboardPosition.png
   :align: center

   Open the dashboard by clicking on :guilabel:`Dashboard`.

..  note::
    If the entry :guilabel:`Dashboard` is not visible in the menu there are two
    possible causes:

    *   You lack sufficient rights to view the dashboard.
    *   The system extension `dashboard` was not installed on your system.

    Ask your administrator about this.

..  _adding-dashboard:

Adding Dashboard
================

The EXT:dashboard allows to have multiple dashboards.
Switching between different dashboards is possible by using the corresponding tab.

In order to add further dashboards, press the :guilabel:`+` sign.

.. figure:: /Images/DashboardTabs.png
   :align: center

   Tabs allowing to switch and add dashboards.

A wizard should open which allows to add the new dashboard.

There you can select a preset. At least the default preset, which is shipped
by core should be available. Depending on system configuration further dashboard
presets might be available.

.. figure:: /Images/DashboardWizard.png
   :align: center

   Wizard to add a new dashboard.

..  _editing-dashboard:

Editing Dashboard
=================

Existing dashboards can be edited and deleted.
On the right side of the tab bar are the icons which allow deletion and adjusting
settings of the currently active dashboard.

.. figure:: /Images/DashboardTabs.png
   :align: center

   Icons on the right side of the tab bar allow adjusting settings or deletion of
   the currently selected dashboard.

..  _adding-widgets:

Adding Widgets
==============

Widgets can be added to a dashboard.
Dashboards which do not contain any widget yet, offer a dialog in the middle of
the screen, which allows to add one or more widgets to the current dashboard.

All dashboards allow to add further widgets in the lower right corner through the
:guilabel:`+` Icon.

.. figure:: /Images/AddWidget.png
   :align: center

   Empty dashboard with possibilities to add new widgets.

Once the action to add a new widget was triggered, a wizard opens which allows to
select the widget to add.

Widgets are grouped in tabs and can be added by clicking on them.

.. figure:: /Images/WidgetWizard.png
   :align: center

   Wizard to select a new widget that will be added to the active dashboard.

..  _widgets-configuration:

Widget configuration
====================

..  versionadded:: 14.0
    `Configurable Dashboard Widgets <https://docs.typo3.org/permalink/changelog:feature-107036-1738837673>`_
    have been introduced with TYPO3 14.0.

*   Dashboard widgets display a settings (cog) icon when they support configuration
*   Clicking the settings icon opens a modal dialog with configurable options
*   Settings are applied immediately after saving, with the widget content
    refreshing automatically
*   Each widget can be configured independently per user / per instance


..  figure:: /Images/DashboardConfiguration.png
    :alt: Screenshot of the dashboard widget "RSS Feed" with the location of the settings (cog) icon

    Click the settings (cog) icon to configure a feed

Extension authors can make their widgets configurable:
`Configurable dashboard widget implementation <https://docs.typo3.org/permalink/typo3/cms-dashboard:configurable-widget-implementation>`_.

..  _moving-widgets:

Moving Widgets
==============

Widgets can be moved around. Therefore a widget needs to be hovered.
If a widget is hovered some icons appear in the upper right corner of the widget.

To move the widget, click and hold left mouse button on the cross icon.
Then move to the target position.

.. figure:: /Images/WidgetMove.png
   :align: center

   Widget in hover mode with additional icons in upper right corner.

..  _deleting-widgets:

Deleting Widgets
================

To delete a widget, the widget needs to be hovered.
If a widget is hovered some icons appear in the upper right corner of the widget.

Click the trash icon which appears to delete the widget.

.. figure:: /Images/WidgetMove.png
   :align: center

   Widget in hover mode with additional icons in upper right corner.

In order to prevent accidentally deletion, a modal is shown to confirm deletion.
Confirm by clicking the :guilabel:`Remove` button.

.. figure:: /Images/WidgetDelete.png
   :align: center

   Modal to confirm deletion of widget.
