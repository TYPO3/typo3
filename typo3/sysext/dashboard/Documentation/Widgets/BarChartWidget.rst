.. include:: /Includes.rst.txt

.. highlight:: php

.. _bar-chart-widget:

================
Bar Chart Widget
================

.. php:namespace:: TYPO3\CMS\Dashboard\Widgets

Widgets using this class will show a bar chart with the provided data.

This kind of widgets are useful if you want to show some statistics of for example
historical data.

.. program:: TYPO3\CMS\Dashboard\Widgets\BarChartWidget

Example
-------

:file:`Configuration/Services.yaml`::

   services:

      dashboard.widget.sysLogErrors:
       class: 'TYPO3\CMS\Dashboard\Widgets\BarChartWidget'
       arguments:
         $dataProvider: '@TYPO3\CMS\Dashboard\Widgets\Provider\SysLogErrorsDataProvider'
         $view: '@dashboard.views.widget'
         $buttonProvider: '@TYPO3\CMS\Dashboard\Widgets\Provider\SysLogButtonProvider'
         $options:
            refreshAvailable: true
       tags:
         - name: dashboard.widget
           identifier: 'sysLogErrors'
           groupNames: 'systemInfo'
           title: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.sysLogErrors.title'
           description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.sysLogErrors.description'
           iconIdentifier: 'content-widget-chart-bar'
           height: 'medium'
           width: 'medium'

Options
-------

.. include:: Options/RefreshAvailable.rst.txt

Dependencies
------------

.. option:: $dataProvider

   To add data to a Bar Chart widget, you need to have a DataProvider that implements
   the interface :php:class:`ChartDataProviderInterface`.

   See :ref:`graph-widget-implementation` for further information.

.. option:: $buttonProvider

   Optionally you can add a button with a link to some additional data.
   This button should be provided by a ButtonProvider that implements the interface :php:class:`ButtonProviderInterface`.

   See :ref:`adding-buttons` for further info and configuration options.

.. option:: $view

   Used to render a Fluidtemplate.
   This should not be changed.
   The default is to use the pre configured Fluid StandaloneView for EXT:dashboard.

   See :ref:`implement-new-widget-fluid` for further information.
