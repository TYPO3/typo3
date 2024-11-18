.. include:: /Includes.rst.txt

..  _list-widget:

===========
List Widget
===========

.. php:namespace:: TYPO3\CMS\Dashboard\Widgets
.. php:class:: TYPO3\CMS\Dashboard\Widgets\ListWidget

Widgets using this class will show a simple list of items provided by a data
provider.

..  _list-widget-example:

Example
-------

..  code-block:: yaml
    :caption: Excerpt from EXT:dashboard/Configuration/Services.yaml

    services:
      dashboard.widget.testList:
        class: 'TYPO3\CMS\Dashboard\Widgets\ListWidget'
        arguments:
          $dataProvider: '@Vendor\Ext\Widgets\Provider\TestListWidgetDataProvider'
          $options:
             refreshAvailable: true
        tags:
          - name: dashboard.widget
            identifier: 'testList'
            groupNames: 'general'
            title: 'List widget'
            description: 'Description of widget'
            iconIdentifier: 'content-widget-list'
            height: 'large'
            width: 'large'

..  _list-widget-options:

Options
-------

.. include:: Options/RefreshAvailable.rst.txt

..  _list-widget-dependencies:

Dependencies
------------

..  confval:: $dataProvider
    :type: :php:`\TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface`
    :name: list-widget-dataProvider

    This class should provide the items to show.
    This data provider needs to implement the
    :php:short:`\TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface`.
