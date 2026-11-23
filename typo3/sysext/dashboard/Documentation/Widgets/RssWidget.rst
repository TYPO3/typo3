.. include:: /Includes.rst.txt

..  _rss-widget:

==========
RSS Widget
==========

..  versionchanged:: 14.0
    The RSS widget was extended to support Atom feeds (commonly used by GitHub,
    GitLab, and other platforms)

.. php:namespace:: TYPO3\CMS\Dashboard\Widgets
.. php:class:: TYPO3\CMS\Dashboard\Widgets\RssWidget

Widgets using this class will show a list of items of the configured RSS feed
or Atom feed.

..  figure:: /Images/Widgets/RssWidget.png
    :alt: RSS widget with the latest TYPO3 news

    The widget "TYPO3 news" shipped with the dashboard extension is
    an RSS widget.

The "RSS Widget" supports both RSS and Atom feeds via automatic detection.

Widget instances are fully configurable with custom labels, feed URLs, and
display limits. Each widget can be configured independently, allowing multiple
feeds of different formats on the same dashboard

Automatic caching ensures optimal performance with configurable cache lifetimes.

..  contents::

..  _rss-widget-usage:

Usage of the RSS Widget in the dashboard
========================================

You can use this kind of widget to show your own RSS feed
or Atom feed.

#.  Navigate to the dashboard where you want to add the widget
#.  Click "Add widget" and select the RSS widget
#.  Click the settings (cog) icon to customize the widget
#.  Configure the feed URL (RSS or Atom), limit, and label as needed
#.  Save the configuration to apply changes

..  _rss-widget-allowed-hosts:

Restricting the hosts of the feeds
==================================

..  versionadded:: 14.3.12
    :changelog: important-110471-1792652849

    Before, the widget also read feeds from other sources, such as local
    files, and the settings of a widget could override the feed URL of the
    widget registration.

The RSS widget fetches feeds over HTTP and HTTPS only.

By default, the widget can request any host. A backend user who can
configure an RSS widget can therefore make the server send requests to any
host, also to hosts in an internal network. Restrict the hosts in
:php:`$GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['dashboard_rss']`.
Allow only the hosts of the feeds that your dashboards use:

..  code-block:: php
    :caption: config/system/additional.php

    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['dashboard_rss'] = [
      // Hosts of the TYPO3 news feed
      'typo3.org',
      'news.typo3.com',
    ];

The restriction applies to every feed request, also to redirect targets and
to the feed URL of a widget registration. If a host is not allowed, the
widget does not load the feed and shows a notice instead.

For wildcards and the values that allow or block all hosts, see
`the allowed hosts in TYPO3 Explained <https://docs.typo3.org/permalink/t3coreapi:typo3confvars-http-allowed-hosts>`_.

..  _rss-widget-format:

Feed format support
===================

The RSS widget now supports both feed formats:

**RSS Feeds:**

Item titles
    Displayed as clickable links
Publication dates
    Used for sorting entries (newest first)
Descriptions
    Displayed as entry content (HTML tags stripped)

**Atom Feeds:**

Entry titles
    Displayed as clickable links
Publication dates
    Used for sorting entries (newest first)
Content/Summary
    Displayed as entry description (HTML tags stripped)
Author information
    Name, email, and URL when provided in the feed

..  _rss-widget-example:

Example for RSS widget with Atom feed
=====================================

..  literalinclude:: _codesnippets/_rsswidget-services.yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

..  _rss-widget-options:

Options
=======

..  include:: Options/RefreshAvailable.rst.txt

The following options are available via :yaml:`services.dashboard.widget.t3news.arguments.$options`:

..  confval:: label
    :type: string
    :name: rss-widget-label

    *   Custom title for the widget instance
    *   Optional field that defaults to the widget's default title

..  confval:: feedUrl
    :type: string
    :name: rss-widget-feedUrl
    :changed: 14.3.12 important-110471-1792652849

    Defines the URL providing the RSS Feed.
    This is fetched by the widget in order to show its entries.

    If the widget registration sets this option, the settings of a widget
    cannot override it. The URL must use HTTP or HTTPS, and its host must be
    allowed, see
    `restricting the hosts of the feeds <https://docs.typo3.org/permalink/typo3/cms-dashboard:rss-widget-allowed-hosts>`_.

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

..  _rss-widget-dependencies:

Dependencies
============

..  confval:: $buttonProvider
    :type: :php:`\TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface`
    :name: rss-widget-buttonProvider

    Provides an optional button to show which is used to open the source of RSS data.
    This button should be provided by a ButtonProvider that implements the interface
    :php-short:`\TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface`.

    See :ref:`adding-buttons` for further info and configuration options.
