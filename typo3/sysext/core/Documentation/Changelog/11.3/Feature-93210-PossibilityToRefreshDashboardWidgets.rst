.. include:: /Includes.rst.txt

==========================================================
Feature: #93210 - Possibility to refresh dashboard widgets
==========================================================

See :issue:`93210`

Description
===========

For some widgets it makes sense for users to be able to refresh the widget
without reloading the complete dashboard. Therefore, a new refresh action
button will be available in the top right corner of widgets, which have the
refresh option enabled.

To enable the refresh action button, you have to define the
:yaml:`refreshAvailable` option in the :yaml:`$options` part of the widget
registration. Below is an example of a RSS widget with the refresh option enabled.

.. code-block:: yaml

   dashboard.widget.myOwnRSSWidget:
     class: 'TYPO3\CMS\Dashboard\Widgets\RssWidget'
     arguments:
       $view: '@dashboard.views.widget'
       $cache: '@cache.dashboard.rss'
       $options:
         rssFile: 'https://typo3.org/rss'
         lifeTime: 43200
         refreshAvailable: true
     tags:
       - name: dashboard.widget
         identifier: 'myOwnRSSWidget'
         groupNames: ‘general’
         title: 'LLL:EXT:extension/Resources/Private/Language/locallang.xlf:widgets.myOwnRSSWidget.title'
         description: 'LLL:EXT:extension/Resources/Private/Language/locallang.xlf:widgets.myOwnRSSWidget.description'
         iconIdentifier: 'content-widget-rss'
         height: 'medium'
         width: 'medium'

.. note::

   In this example, the TYPO3 Core :php:`TYPO3\CMS\Dashboard\Widgets\RssWidget`
   widget class is used. In case you have implemented own widget classes, you
   have to add the :php:`getOptions()` method, returning :php:`$this->options`,
   to the corresponding classes. Otherwise the refresh option won't have any
   effect. The method will anyways be required by the :php:`WidgetInterface`
   in TYPO3 v12.

JavaScript API
==============

Besides having a refresh action button on widgets, which have the new option
enabled, it is possible for all widgets to dispatch an event, which will cause
the widget being refreshed. This is possible for all widgets on the dashboard
even when the :yaml:`refreshAvailable` option is not defined, or set to `false`.
This will give developers the option to refresh the widgets whenever they think
it is appropriate.

You therefore need to dispatch the :js:`widgetRefresh` event on the
widget container (the :html:`div` element with the :html:`dashboard-item` class).
You can identify the container by the data attribute :html:`widget-hash`, which
is a unique hash for every widget, even if you have more widgets of the same
type on your dashboard.

A small example below:

.. code-block:: javascript

   document
     .querySelector('[data-widget-hash="{your-unique-widget-hash}"]')
     .dispatchEvent(new Event('widgetRefresh', {bubbles: true}));


Impact
======

Widgets with the option :yaml:`refreshAvailable` set to `true`, will now
feature a refresh action button in the top right corner of the widget.

.. index:: Backend, ext:beuser
