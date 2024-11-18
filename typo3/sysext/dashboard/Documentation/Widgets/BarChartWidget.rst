.. include:: /Includes.rst.txt

..  _bar-chart-widget:

================
Bar Chart Widget
================

.. php:namespace:: TYPO3\CMS\Dashboard\Widgets

Widgets using this class will show a bar chart with the provided data.

This kind of widgets are useful if you want to show some statistics of for example
historical data.

.. php:class:: TYPO3\CMS\Dashboard\Widgets\BarChartWidget

..  _bar-chart-widget-example:

Example
-------

..  code-block:: yaml
    :caption: Excerpt from EXT:dashboard/Configuration/Services.yaml

    services:
      dashboard.widget.sysLogErrors:
        class: 'TYPO3\CMS\Dashboard\Widgets\BarChartWidget'
        arguments:
          $dataProvider: '@TYPO3\CMS\Dashboard\Widgets\Provider\SysLogErrorsDataProvider'
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

..  _bar-chart-widget-options:

Options
-------

.. include:: Options/RefreshAvailable.rst.txt

..  _bar-chart-widget-dependencies:

Dependencies
------------

..  confval:: $dataProvider
    :type: :php:`\TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface`
    :name: bar-chart-widget-dataProvider

    To add data to a Bar Chart widget, you need to have a DataProvider that implements
    the interface :php-short:`\TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface`.

    See :ref:`graph-widget-implementation` for further information.

..  confval:: $buttonProvider
    :type: :php:`\TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface`
    :name: bar-chart-widget-buttonProvider

    Optionally you can add a button with a link to some additional data.
    This button should be provided by a ButtonProvider that implements the interface
    :php-short:`\TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface`.

    See :ref:`adding-buttons` for further info and configuration options.
