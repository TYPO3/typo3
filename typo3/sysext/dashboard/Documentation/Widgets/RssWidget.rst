.. include:: /Includes.rst.txt

.. _rss-widget:

==========
RSS Widget
==========

.. php:namespace:: TYPO3\CMS\Dashboard\Widgets
.. php:class:: TYPO3\CMS\Dashboard\Widgets\RssWidget

Widgets using this class will show a list of items of the configured RSS feed.

You can use this kind of widget to create a widget showing your own RSS feed.

Example
-------

..  code-block:: yaml
    :caption: Excerpt from EXT:dashboard/Configuration/Services.yaml

    services:
      cache.dashboard.rss:
        class: 'TYPO3\CMS\Core\Cache\Frontend\FrontendInterface'
        factory: ['@TYPO3\CMS\Core\Cache\CacheManager', 'getCache']
        arguments:
          $identifier: 'dashboard_rss'

      dashboard.buttons.t3news:
        class: 'TYPO3\CMS\Dashboard\Widgets\Provider\ButtonProvider'
        arguments:
          $title: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.moreItems'
          $link: 'https://typo3.org/project/news'
          $target: '_blank'

      dashboard.widget.t3news:
        class: 'TYPO3\CMS\Dashboard\Widgets\RssWidget'
        arguments:
          $view: '@dashboard.views.widget'
          $cache: '@cache.dashboard.rss'
          $buttonProvider: '@dashboard.buttons.t3news'
          $options:
            feedUrl: 'https://www.typo3.org/rss'
            # 12 hours cache
            lifeTime: 43200
            refreshAvailable: true
        tags:
          - name: dashboard.widget
            identifier: 't3news'
            groupNames: 'typo3'
            title: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.title'
            description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.description'
            iconIdentifier: 'content-widget-rss'
            height: 'large'
            width: 'medium'

Options
-------

.. include:: Options/RefreshAvailable.rst.txt

The following options are available via :yaml:`services.dashboard.widget.t3news.arguments.$options`:

..  confval:: feedUrl
    :type: string
    :name: rss-widget-feedUrl

    Defines the URL or file providing the RSS Feed.
    This is read by the widget in order to fetch entries to show.

..  confval:: lifeTime
    :type: int
    :name: rss-widget-lifeTime
    :Default: `43200`

    Defines how long to wait, in seconds, until fetching RSS Feed again.

..  confval:: limit
    :type: int
    :name: rss-widget-limit
    :Default: `5`

    Defines how many RSS items should be shown.

Dependencies
------------

..  confval:: $buttonProvider
    :type: :php:`\TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface`
    :name: rss-widget-buttonProvider

    Provides an optional button to show which is used to open the source of RSS data.
    This button should be provided by a ButtonProvider that implements the interface
    :php-short:`\TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface`.

    See :ref:`adding-buttons` for further info and configuration options.

..  confval:: $view
    :name: rss-widget-view

    Used to render a Fluidtemplate.
    This should not be changed.
    The default is to use the pre configured Fluid StandaloneView for EXT:dashboard.

    See :ref:`implement-new-widget-fluid` for further information.

..  confval:: $cache
    :name: rss-widget-cache

    Used to cache fetched RSS items.
    This should not be changed.
