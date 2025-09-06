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

The "RSS Widget" supports both RSS and Atom feeds via automatic detection.

Widget instances are fully configurable with custom labels, feed URLs, and
display limits. Each widget can be configured independently, allowing multiple
feeds of different formats on the same dashboard

Automatic caching ensures optimal performance with configurable cache lifetimes.

..  contents::

..  _rss-widget-usage:

Usage of the RSS Widget in the dashboard
----------------------------------------

You can use this kind of widget to show your own RSS feed
or Atom feed.

#.  Navigate to the dashboard where you want to add the widget
#.  Click "Add widget" and select the RSS widget
#.  Click the settings (cog) icon to customize the widget
#.  Configure the feed URL (RSS or Atom), limit, and label as needed
#.  Save the configuration to apply changes

..  _rss-widget-format:

Feed format support
-------------------

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
-------------------------------------

..  literalinclude:: _codesnippets/_rsswidget-services.yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

..  _rss-widget-options:

Options
-------

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

..  _rss-widget-dependencies:

Dependencies
------------

..  confval:: $buttonProvider
    :type: :php:`\TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface`
    :name: rss-widget-buttonProvider

    Provides an optional button to show which is used to open the source of RSS data.
    This button should be provided by a ButtonProvider that implements the interface
    :php-short:`\TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface`.

    See :ref:`adding-buttons` for further info and configuration options.
