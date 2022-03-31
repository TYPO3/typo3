.. include:: /Includes.rst.txt

============================================================
Breaking: #90660 - Registration of dashboard widgets changed
============================================================

See :issue:`90660`

Description
===========

As the registration of dashboard widgets changed to allow creation of widgets
through configuration, it is necessary to change your registration of widgets you
registered yourself in version 10.3. The abstracts used to kick start your
widgets were removed and the widgets shipped with EXT:dashboard were refactored.


Impact
======

As the abstracts previously used to kick-start a widget are removed, you need
to change to the new way of registering widgets. The dashboard
module will break if you do not update your registration.


Affected Installations
======================

All 3rd party extensions that registered an own widget with TYPO3 v10.3, will be
affected and need to update the widget registration. If you only used the widgets
shipped with core, you don't have to do anything.


Migration
=========


Migration of widgets based on default widget types
--------------------------------------------------

This section demonstrates how to migrate widgets that are based on one of
the existing widget types shipped by core. If your widgets are extending
one of the following classes, you can use this section to migrate your registration
to the new syntax.

- :php:`\TYPO3\CMS\Dashboard\Widgets\AbstractBarChartWidget`
- :php:`\TYPO3\CMS\Dashboard\Widgets\AbstractChartWidget`
- :php:`\TYPO3\CMS\Dashboard\Widgets\AbstractCtaButtonWidget`
- :php:`\TYPO3\CMS\Dashboard\Widgets\AbstractDoughnutChartWidget`
- :php:`\TYPO3\CMS\Dashboard\Widgets\AbstractListWidget`
- :php:`\TYPO3\CMS\Dashboard\Widgets\AbstractNumberWithIconWidget`
- :php:`\TYPO3\CMS\Dashboard\Widgets\AbstractRssWidget`

First of all you need to update your registration in the :file:`Services.yaml` file.
Here comes an example of a registration of RSS widget in the old version.

**Before**

.. code-block:: yaml

   Vendor\Package\Widgets\MyOwnRSSWidget:
     arguments: [‘myOwnRSSWidget’]
     tags:
       - name: dashboard.widget
         identifier: myOwnRSSWidget
         widgetGroups: ‘general’


As you can now use the predefined widgets and only have to register your own
implementation with your own configuration, you have to alter this registration
a little bit.

**Now**

.. code-block:: yaml

   dashboard.widget.myOwnRSSWidget:
     class: 'TYPO3\CMS\Dashboard\Widgets\RssWidget'
     arguments:
       $view: '@dashboard.views.widget'
       $cache: '@cache.dashboard.rss'
       $options:
         rssFile: 'https://typo3.org/rss'
         # 12 hours cache
         lifeTime: 43200
     tags:
       - name: dashboard.widget
         identifier: 'myOwnRSSWidget'
         groupNames: ‘general’
         title: 'LLL:EXT:extension/Resources/Private/Language/locallang.xlf:widgets.myOwnRSSWidget.title'
         description: 'LLL:EXT:extension/Resources/Private/Language/locallang.xlf:widgets.myOwnRSSWidget.description'
         iconIdentifier: 'content-widget-rss'
         height: 'medium'
         width: 'medium'


It starts with the name of the service. Best practise is to use a dot-styled
name as there will be no class with that name. You can have multiple services
using the same class.

On the second line, we define which widget to use. In this case we choose the
RssWidget from the dashboard core extension. In the documentation, we explain
all the arguments like :php:`$view` and :php:`$cache`. For the migration you need
the :php:`$options` argument.

As you can see we specify the RSS file and the cache lifetime for this feed.
In the old situation you had to set these values in the class.
Now you can just put those values in the registration.

The second part that changed a little bit, is that you need to set the title,
description, icon, height and width in the tags section of the registration.
You can still use translatable strings like
``LLL:EXT:extension/Resources/Private/Language/locallang.xlf:widgets.myOwnRSSWidget.title``.
Important to remember is that the :yaml:`widgetGroups` property changed to :yaml:`groupNames`
to stay consistent with other service registrations.

Please note that valid values for height and width are now: :yaml:`small`, :yaml:`medium`,
and :yaml:`large`.

In the following table you can see which WidgetType to use now based on the
abstract you used previously.

+--------------------------------------+----------------------------------------------------------------------+
| Previously used abstract             | Widget class to use for your registration                            |
+======================================+======================================================================+
| :php:`AbstractBarChartWidget`        | :php:`TYPO3\CMS\Dashboard\Widgets\BarChartWidget`                    |
+--------------------------------------+----------------------------------------------------------------------+
| :php:`AbstractChartWidget`           | This was only used as an abstract of the other chart widgets and is  |
|                                      | not used anymore. If you want another graph type, you have to create |
|                                      | your own widget.                                                     |
+--------------------------------------+----------------------------------------------------------------------+
| :php:`AbstractCtaButtonWidget`       | :php:`TYPO3\CMS\Dashboard\Widgets\CtaWidget`                         |
+--------------------------------------+----------------------------------------------------------------------+
| :php:`AbstractDoughnutChartWidget`   | :php:`TYPO3\CMS\Dashboard\Widgets\DoughnutChartWidget`               |
+--------------------------------------+----------------------------------------------------------------------+
| :php:`AbstractListWidget`            | :php:`TYPO3\CMS\Dashboard\Widgets\ListWidget`                        |
+--------------------------------------+----------------------------------------------------------------------+
| :php:`AbstractNumberWithIconWidget`  | :php:`TYPO3\CMS\Dashboard\Widgets\NumberWithIconWidget`              |
+--------------------------------------+----------------------------------------------------------------------+
| :php:`AbstractRssWidget`             | :php:`TYPO3\CMS\Dashboard\Widgets\RssWidget`                         |
+--------------------------------------+----------------------------------------------------------------------+


You can check the documentation of EXT:dashboard to see the exact options for every type of widget.


Migration of widgets based on own widget type
---------------------------------------------

When you created your complete own widget type, the main thing to check is you
use the Dependency Injection options you have now. Please refer to the documentation
of EXT:dashboard to see how to create your own widget type and what options you
have.

.. index:: Backend, ext:dashboard, NotScanned
