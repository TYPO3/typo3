.. include:: /Includes.rst.txt

..  _number-widget:

=======================
Number With Icon Widget
=======================

.. php:namespace:: TYPO3\CMS\Dashboard\Widgets
.. php:class:: TYPO3\CMS\Dashboard\Widgets\NumberWithIconWidget

Widgets using this class will show a widget with a number, some additional
text and an icon.

This kind of widgets are useful if you want to show some simple stats.

..  _number-widget-example:

Example
-------

..  code-block:: yaml
    :caption: Excerpt from EXT:dashboard/Configuration/Services.yaml

    services:
      dashboard.widget.failedLogins:
        class: 'TYPO3\CMS\Dashboard\Widgets\NumberWithIconWidget'
        arguments:
          $dataProvider: '@TYPO3\CMS\Dashboard\Widgets\Provider\NumberOfFailedLoginsDataProvider'
          $options:
            title: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.failedLogins.title'
            subtitle: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.failedLogins.subtitle'
            icon: 'content-elements-login'
            refreshAvailable: true
        tags:
          - name: dashboard.widget
            identifier: 'failedLogins'
            groupNames: 'systemInfo'
            title: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.failedLogins.title'
            description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.failedLogins.description'
            iconIdentifier: 'content-widget-number'

..  _number-widget-options:

Options
-------

..  include:: Options/RefreshAvailable.rst.txt

..  confval:: title
    :type: string
    :name: number-widget-title
    :Example: `LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.failedLogins.title`

    The main title that will be shown in the widget as an explanation of the shown number.
    You can either enter a normal string or a translation string.

..  confval:: subtitle
    :type: string
    :name: number-widget-subtitle
    :Example: `LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.failedLogins.subtitle`

    The optional subtitle that will give some additional information about the number and title.
    You can either enter a normal string or a translation string.

..  confval:: icon
    :type: string
    :name: number-widget-icon

    The icon-identifier of the icon that should be shown in the widget.
    You should register your icon with the :ref:`t3coreapi:icon`.

..  _number-widget-dependencies:

Dependencies
------------

..  confval:: $dataProvider
    :type: :php:`\TYPO3\CMS\Dashboard\Widgets\NumberWithIconDataProviderInterface`
    :name: number-widget-dataProvider

    This class should provide the number to show.
    This data provider needs to implement the
    :php-short:`\TYPO3\CMS\Dashboard\Widgets\NumberWithIconDataProviderInterface`.
