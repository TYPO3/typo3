..  include:: /Includes.rst.txt

..  _feature-107201-1754296608:

========================================================
Feature: #107201 - Extended RSS Widget with Atom Support
========================================================

See :issue:`107201`

Description
===========

Building upon the configurable dashboard widgets functionality introduced in
:ref:`feature-107036-1738837673`, the existing RSS widget has been extended to
support Atom feeds, providing a unified solution for both RSS and Atom feed
formats within the TYPO3 dashboard system.

The configurable widget architecture makes it possible to create feed-based
widgets that users can configure directly through the dashboard interface.
This capability now extends seamlessly to Atom feeds, which are commonly used
by platforms like GitHub for their release feeds and project updates.

The RSS widget now automatically detects and parses both RSS and Atom feed
formats, with Atom feeds being parsed according to the Atom Syndication Format
(RFC 4287). This provides comprehensive support for modern feed formats used by
many development platforms and services, all within a single widget implementation.

Impact
======

* The existing "RSS Widget" now supports both RSS and Atom feeds automatically
* Atom feeds (commonly used by GitHub, GitLab, and other platforms) can now be displayed using the same RSS widget
* Widget instances are fully configurable with custom labels, feed URLs, and display limits
* Each widget can be configured independently, allowing multiple feeds of different formats on the same dashboard
* Automatic caching ensures optimal performance with configurable cache lifetimes

Currently Configurable Options
------------------------------

The RSS widget supports the following configuration options for both RSS and Atom feeds:

**Label**
  * Custom title for the widget instance
  * Optional field that defaults to the widget's default title

**Feed URL**
  * RSS or Atom feed URL to display
  * Format detection is automatic based on feed content
  * Can be pre-configured in service definition to create read-only instances

**Limit**
  * Number of feed entries to show (default: 5)
  * Configurable integer value to control widget content density

**Lifetime**
  * Cache duration in seconds for the feed (default: 43200 = 12 hours)
  * Advanced setting typically configured by integrators
  * Not configurable in the UI

Example for RSS widget with Atom feed
--------------------------------------

**Service Configuration:**

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    services:

      # Button provider for external link
      dashboard.buttons.github_releases:
        class: 'TYPO3\CMS\Dashboard\Widgets\Provider\ButtonProvider'
        arguments:
          $title: 'View all releases'
          $link: 'https://github.com/TYPO3/typo3/releases'
          $target: '_blank'

      # RSS widget with Atom feed URL
      dashboard.widget.github_releases:
        class: 'TYPO3\CMS\Dashboard\Widgets\RssWidget'
        arguments:
          $buttonProvider: '@dashboard.buttons.github_releases'
          $options:
            feedUrl: 'https://github.com/TYPO3/typo3/releases.atom'
            lifeTime: 43200
            limit: 10
        tags:
          - name: dashboard.widget
            identifier: 'github_releases'
            groupNames: 'general'
            title: 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:widgets.github_releases.title'
            description: 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:widgets.github_releases.description'
            iconIdentifier: 'content-widget-rss'
            height: 'large'
            width: 'medium'

**Usage in Dashboard:**

1. Navigate to the dashboard where you want to add the widget
2. Click "Add widget" and select the RSS widget
3. Click the settings (cog) icon to customize the widget
4. Configure the feed URL (RSS or Atom), limit, and label as needed
5. Save the configuration to apply changes

The widget will automatically detect the feed format and display entries with titles,
publication dates, content summaries, and author information when available.

Feed Format Support
-------------------

The RSS widget now supports both feed formats:

**RSS Feeds:**
* **Item titles** - Displayed as clickable links
* **Publication dates** - Used for sorting entries (newest first)
* **Descriptions** - Displayed as entry content (HTML tags stripped)

**Atom Feeds:**
* **Entry titles** - Displayed as clickable links
* **Publication dates** - Used for sorting entries (newest first)
* **Content/Summary** - Displayed as entry description (HTML tags stripped)
* **Author information** - Name, email, and URL when provided in the feed

.. index:: Backend, Dashboard, Atom, RSS, ext:dashboard
