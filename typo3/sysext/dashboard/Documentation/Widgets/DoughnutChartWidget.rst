.. include:: /Includes.rst.txt

.. _doughnut-chart-widget:

=====================
Doughnut Chart Widget
=====================

.. php:namespace:: TYPO3\CMS\Dashboard\Widgets
.. program:: TYPO3\CMS\Dashboard\Widgets\DoughnutChartWidget

Widgets using this class will show a doughnut chart with the provided data.

This kind of widgets are useful if you want to show the relational proportions
between data.

Example
-------

:file:`Configuration/Services.yaml`::

   services:

    dashboard.widget.typeOfUsers:
       class: 'TYPO3\CMS\Dashboard\Widgets\DoughnutChartWidget'
       arguments:
         $view: '@dashboard.views.widget'
         $dataProvider: '@TYPO3\CMS\Dashboard\Widgets\Provider\TypeOfUsersChartDataProvider'
         $options:
            refreshAvailable: true
       tags:
         - name: dashboard.widget
           identifier: 'typeOfUsers'
           groupNames: 'systemInfo'
           title: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.typeOfUsers.title'
           description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.typeOfUsers.description'
           iconIdentifier: 'content-widget-chart-pie'
           height: 'medium'

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
