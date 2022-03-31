.. include:: /Includes.rst.txt

.. _list-widget:

===========
List Widget
===========

.. php:namespace:: TYPO3\CMS\Dashboard\Widgets
.. program:: TYPO3\CMS\Dashboard\Widgets\ListWidget

Widgets using this class will show a simple list of items provided by a data
provider.

Example
-------

:file:`Configuration/Services.yaml`::

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

Options
-------

.. include:: Options/RefreshAvailable.rst.txt

Dependencies
------------

.. option:: $dataProvider

   This class should provide the items to show.
   This data provider needs to implement the :php:class:`ListDataProviderInterface`.
