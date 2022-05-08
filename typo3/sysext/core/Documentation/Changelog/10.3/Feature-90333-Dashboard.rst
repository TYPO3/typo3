.. include:: /Includes.rst.txt

===========================
Feature: #90333 - Dashboard
===========================

See :issue:`90333`

Description
===========

A dashboard is introduced into TYPO3 to show relevant information to
the current logged in user.

Every user with access to this backend module can now have one or more personal
dashboards. Each dashboard can contain several widgets. Which widgets and in which
order the widgets are shown is up to the users themselves.

As an integrator, you have the possibility to create dashboard templates. You can
mark the template as a default template so it will be created by default for every
new user.

As a developer, you can create your own widgets. Just use one of the available
abstracts, which will be extended in the future, and extend it with your own
information.

You can find the new dashboard in the toolbar in the top of your window.


Available widgets
^^^^^^^^^^^^^^^^^

The following widgets are shipped by core extensions now:

* TYPO3 news: A widget showing the latest 5 news items from typo3.org (EXT:dashboard)
* TYPO3 security advisories: A widget showing the latest 5 security advisories from typo3.org (EXT:dashboard)
* TYPO3: This widget will show you some background information about TYPO3 and shows the current version of TYPO3 installed (EXT:dashboard)
* Getting started with TYPO3: This widget will provide a link to the Getting Started Tutorial (EXT:dashboard)
* TypoScript Template Reference: This widget will provide a link to the TypoScript Template Reference (EXT:dashboard)
* TSconfig Reference: This widget will provide a link to the TSconfig Reference (EXT:dashboard)
* Number of errors in system log: Shows the number of errors in the sys_log grouped by day for the last month (EXT:dashboard)
* Type of backend users: A widget to show the different types of backend users (EXT:dashboard)
* Failed Logins: This widget will show you the number of failed logins during the last 24 hours (EXT:dashboard)


Creating your own widget
^^^^^^^^^^^^^^^^^^^^^^^^

Besides the widgets shipped with TYPO3 core, you can also write your own widget. To
do so, you can extend one of the WidgetAbstracts available in EXT:dashboard.

* :php:`AbstractWidget`: a basic abstract that can be used as the start of simple widgets
* :php:`AbstractRssWidget`: with this abstract it is easy to create a widget showing a RSS feed
* :php:`AbstractListWidget`: this abstract will give you an easy start to show a list of items
* :php:`AbstractCtaButtonWidget`: when you want to show a Call-To-Action button, this is the right abstract
* :php:`AbstractChartWidget`: the base of all chart widgets
* :php:`AbstractBarChartWidget`: when you want to show a widget with a bar-chart you can extend this class
* :php:`AbstractDoughnutChartWidget`: this abstract gives you the possibility to create a doughnut-chart widget
* :php:`AbstractNumberWithIconWidget`: this abstract will give you the possibility to show a title, number and an icon


By extending one of those abstracts, and providing it with the needed data, you are able to
have a new widget quite fast. The only thing that is left is to register the widget.

Tag your widget in :file:`EXT:your_extension/Configuration/Services.yaml`:

.. code-block:: yaml

      # Variant 1, widget identifier as attribute
      Vendor\Extension\Widgets\MyFirstWidget:
        arguments: ['widget-identifier-1']
        tags:
          - name: dashboard.widget
            identifier: widget-identifier-1
            widgetGroups: 'general'

      # Variant 2, custom service name, allows multiple widget identifiers
      # to share the same class
      widget.identifier:
        class: Vendor\Extension\Widgets\MySecondWidget
        arguments: ['widget-identifier-1']
        tags:
          - name: dashboard.widget
            # If omitted, the identifier would be the service name, thus 'widget.identifier'
            identifier: widget-identifier-2
            widgetGroups: 'general, typo3'


Every widget needs a unique identifier, the implementing class and at least one
associated widget group. Multiple widget groups are separated by comma.


Configuring Widget Groups
^^^^^^^^^^^^^^^^^^^^^^^^^

Every widget is attached to one or more widget groups. Those groups are shown in the
modal when adding a new widget to your dashboard. In this way you can group the available
widgets to get a clear overview for your users. By default the following widget groups are
available:

* `widgetGroup-general`: Widgets with a more generic purpose
* `widgetGroup-systemInfo`: Widgets which provide system related information
* `widgetGroup-typo3`: Widgets with information regarding the TYPO3 product or community
* `widgetGroup-documentation`: Widgets with links to TYPO3 documentation

You can also configure your own widget groups. To do so, you create a file :file:`EXT:your_extension/Configuration/Backend/DashboardWidgetGroups.php`.
In that file you specify the information of the groups.

.. code-block:: php

   return [
      'widgetGroup-myOwnGroup' => [
         'title' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:widget_group.myOwnGroup',
      ],
   ];

First you have the identifier as the key. This identifier is used to map widgets to this
group. You only have one property and that is the title. You can add a simple text, or a
translation string like in the example above.


Defining Dashboard Presets
^^^^^^^^^^^^^^^^^^^^^^^^^^

You have the possibility to create dashboard presets. Those
presets are used when a user is creating a new dashboard. He can choose one of the available presets.
In a preset you can define which widgets and in which order those widgets will be added to the dashboard when created.

So for example, if you want to give editors the possibility to add a dashboard with several
SEO related widgets, you can create a dashboard preset and add all the useful widgets on that.
When a user creates a dashboard based on that preset, all those widgets will be initially added to
that dashboard.

To define those dashboard presets, you can create a file :file:`EXT:your_extension/Configuration/Backend/DashboardPresets.php`.
In that file you specify the information of the presets.

.. code-block:: php

   return [
       'dashboardPreset-myOwnPreset' => [
           'title' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:dashboard.myOwnPreset',
           'description' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:dashboard.myOwnPreset.description',
           'iconIdentifier' => 'content-dashboard',
           'defaultWidgets' => ['widget-identifier-1', 'widget-identifier-2'],
           'showInWizard' => true
       ],
   ];

You start again with the dashboard preset identifier which should be unique. Every preset needs a title, description, iconIdentifier, some widgets and a flag if the
preset should be shown in the wizard to create a new dashboard. This last setting is to make it possible to not show it as a preset, but it can be used to
create this dashboard preset by default for new users.


Automatically create a dashboard for new users
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you have a new user in your backend, you might want to kickstart that user and provide a
basic dashboard. You can do this by defining which dashboard presets should be created by default
when a user gets created or when a user deletes all his dashboards.

You can define which dashboards will be created automatically by using the following TSconfig setting:

.. code-block:: typoscript

   options.dashboard.dashboardPresetsForNewUsers = default, dashboardPreset-myOwnPreset

You can add the identifiers of multiple presets in a comma separated list.


Permissions
^^^^^^^^^^^

As widgets might contain sensitive information, it is also possible to define the permissions
of the widgets on a group base. In the backend group settings you have the possibility to allow
specific widgets. Only those widgets will be available for users in that group. Admin users
have access to all widgets by default.


Impact
======

This is a new backend module and will not replace any old features. If the dashboard
extension is installed, it will be the default startup page in TYPO3 Backend.

.. index:: Backend, Dashboard, ext:dashboard
