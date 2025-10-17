.. include:: /Includes.rst.txt
.. _indexing-configurations:

=======================
Indexing Configurations
=======================

..  contents:: Table of contents

..  _crawler-setup:

Setting up the "crawler" extension
==================================

Before you can work with **Indexing Configurations** you must make sure
you have set up the extension :composer:`tomasnorre/crawler` and have a cron-job running
that will process the crawler queue as we fill it. For this, please
refer to the
`Manual of the Crawler extension <https://docs.typo3.org/p/tomasnorre/crawler/main/en-us/Configuration/Index.html>`_.

..  _about-indexing-configurations:

Generally about Indexing Configurations
=======================================

Indexing configuration sets up indexing jobs that are performed by a
cron-script independently of frontend requests. The "crawler"
extension is used as a service to perform the execution of queue
entries that controls the indexing.

You can create an indexing configuration in the :guilabel:`Web > List` module
in on any page. Where to place the configuration depends on the type of
data that should be indexed. See following sections.

..  figure:: /Images/IndexingConfiguration/IndexingConfiguration.png
    :alt: Screenshot of an indexing configuration record in the List module of the TYPO3 backend

    Common parameters in Indexing Configurations

The "Session ID" requires a show introduction: When an indexing job is
started it will set this value to a unique number which is used as ID
for that process and all indexed entries are tagged with it. When the
processing of an indexing configuration is done it will be reset to
zero again.

..  _periodic-indexing-website:

Periodic indexing of the website ("Page tree")
==============================================

You can have the whole page tree indexed overnight using this indexing
configuration of type "Page tree":

*   Type: Page tree
*   Root page: Your start page
*   Depth: 4 Levels (or as many as there are)

Using the :guilabel:`Web > List` module create this indexing configuration in
a system folder on your site.

For each page a combination of
parameters is calculated based on the "crawler" configurations for the
"Re-index" processing instruction (See "crawler" extension for more
information) and those URLs are committed to the crawler log plus
entries for all subpages to the processed page (so that each of those
pages are indexed as well.)

The rest of the configuration, for example with which parameter to call
the pages is made in the :composer:`tomasnorre/crawler` extension.

..  _periodic-indexing-records:

Periodic indexing of records ("Database Records")
=================================================

You can also use the Indexing Configuration to index single records.

**Location:** You must place the indexing configuration on the page
where you want the search results to be displayed. For example when you want to
index news entries, place the configuration on the page that contains the
single view plugin of news.

*   Type: "Database Records"
*   Table to index: For example: "News"
*   Alternative Source Page: The page that contains the records, for example the news folder
*   Fields: For example: "title, short, text"
*   GET parameter string: For example: "&tx_news[action]=show&tx_news[news]=###UID###".
    The chash will be automatically attached. This must correspond with
    what the plugin takes of parameters.

If a record is removed its indexing entry will also be
removed upon next indexing. The UID of the record is saved in the index for
that purpose.

..  _indexing-external-websites:

Indexing External websites ("External URL")
===========================================

Using the crawler extension, you can index external websites using
Indexing Configurations.

*   External URL: `https://example.org`
*   Depth: 1 Level
*   Enter sub-URLs in which not to descend: `https://example.org/black_hole`

**Location:** You should place the Indexing Configuration on a "Not-
in-menu" page in the root of the site for instance. The page must be
"searchable" since the external URL results are bound to a page in the
page tree, namely the page where the configuration is found.

..  _indexing-directories-of-files-filepath-on-server:
..  _indexing-files-separately:

Indexing directories of files ("Filepath on server")
====================================================

You can also have directories of files on your server indexed
periodically, using the type "Filepath on server".

*   Filepath: `fileadmin/user_upload/my_pdfs`
*   Limit to extensions: pdf, txt
*   Depth: 2 Levels

**Location:** The Indexed Search configuration should be located on a not-
in-menu page, just like the "External URL" type required. Same
reasons; results are bound to a page in the page tree.

For each directory:

#.  all files are indexed and
#.  all sub-directories added to the crawler queue for later processing.

..  _showing-search-results:

Showing the search results
==========================

By default the search results are shown with no distinction between
those from local TYPO3 pages, records indexed, the file path and
external URLs. The only division that follows is that of the page on which the
result is found.

However, you can configure to have a division of the search results
into categories following the Indexing Configurations.

To obtain this categorization you must set TypoScript configuration in
the Setup field like this:

..  code-block:: typoscript
    :caption: packages/my_site_package/Configuration/Sets/MySet/setup.typoscript

    plugin.tx_indexedsearch.settings.defaultFreeIndexUidList = 0,6,7,8
    plugin.tx_indexedsearch.settings.blind.freeIndexUid = 0

The "defaultFreeIndexUidList" is uid numbers of indexing
configurations to show in the categorization! The order determines
which are shown in top.

The categorization is only displayed, when the "Category" selector in the
"Advanced" search form is set to "All categorized". You can preset the
selector to use this setting by default:
:ref:`plugin.tx_indexedsearch.settings.defaultOptions.freeIndexUid <typo3/cms-indexed-search:confval-defaultoptions-freeindexuid>`.

For example:

..  code-block:: typoscript
    :caption: packages/my_site_package/Configuration/Sets/MySet/setup.typoscript

    plugin.tx_indexedsearch.settings.defaultOptions.freeIndexUid = -2

..  _showing-search-results-specific:

Searching in a specific category
--------------------------------

In the advanced search users can pick a special category from the "Category"
selector to limit results to this Indexing Configuration.

You can also limit the search form by default by setting 0 for pages or the
UID of an Indexing Configuration for any other indexing type:

..  code-block:: typoscript
    :caption: packages/my_site_package/Configuration/Sets/MySet/setup.typoscript

    # Search only in pages
    plugin.tx_indexedsearch.settings.defaultOptions.freeIndexUid = 0

    # Search only in news, use uid of the Indexing Configuration
    plugin.tx_indexedsearch.settings.defaultOptions.freeIndexUid = 42

..  _grouping-indexing-configurations:

Grouping several Indexing Configurations in one search category
===============================================================

You might find that you want to group the results from multiple
Indexing Configurations in the same category.

This can be done by creating a special type of indexing configuration which
only points to other Indexing Configurations:

*   Type: Meta configuration
*   Indexing Configurations (chose those that should be included)

This Indexing Configuration is not used during indexing but during
searching only.

..  _disable-frontend-indexing:

Disable frontend-initiated indexing
===================================

If you choose to index your site using Indexing Configurations you can
disable indexing through the user requests in the frontend. This is
done via the module :guilabel:`Admin Tools > Settings > Extension Configuration`.

Toggle the configuration option "Disable Indexing in Frontend".
