.. include:: /Includes.rst.txt

.. highlight:: php

.. _make-refreshable:

==================
The refresh option
==================

In each widget the refresh option can be enabled. If the option is enabled the
widget displays a reload button in the top right corner. It can then be
refreshed via user interaction or via a javascript api.

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
         groupNames: â€˜generalâ€™
         title: 'LLL:EXT:extension/Resources/Private/Language/locallang.xlf:widgets.myOwnRSSWidget.title'
         description: 'LLL:EXT:extension/Resources/Private/Language/locallang.xlf:widgets.myOwnRSSWidget.description'
         iconIdentifier: 'content-widget-rss'
         height: 'medium'
         width: 'medium'

.. note::

   In this example, the TYPO3 core :php:`TYPO3\CMS\Dashboard\Widgets\RssWidget`
   widget class is used. In case you have implemented own widget classes, you
   have to add the :php:`getOptions()` method, returning :php:`$this->options`,
   to the corresponding classes. Otherwise the refresh option won't have any
   effect. The method will anyways be required by the :php:`WidgetInterface`
   in TYPO3 v12.

Enable the refresh button
-------------------------

Widgets can render a refresh button to allow users to manually refresh them.

This is done by passing the value :php:`['refreshAvailable'] = true;` back
via :php:`getOptions()` method of the widget.

All TYPO3 Core widgets implement this behaviour and allow integrators to
configure the option:

.. include:: /Widgets/Options/RefreshAvailable.rst.txt

JavaScript API
--------------

It is possible for all widgets to dispatch an event, which will cause
the widget being refreshed. This is possible for all widgets on the dashboard
even when the :yaml:`refreshAvailable` option is not defined, or set to `false`.
This will give developers the option to refresh the widgets whenever they think
it is appropriate.

To refresh a widget, dispatch the :js:`widgetRefresh` event on the
widget container (the :html:`div` element with the :html:`dashboard-item` class).
You can identify the container by the data attribute :html:`widget-hash`, which
is a unique hash for every widget, even if you have more widgets of the same
type on your dashboard.

A small example below:

.. code-block:: javascript

   document
     .querySelector('[data-widget-hash="{your-unique-widget-hash}"]')
     .dispatchEvent(new Event('widgetRefresh', {bubbles: true}));

See :ref:`implement-new-widget-custom-js` to learn how to add custom JavaScript.
