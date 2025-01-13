..  include:: /Includes.rst.txt
..  index:: XML sitemap
..  _xmlsitemap:

===========
XML sitemap
===========

:composer:`typo3/cms-seo` provides a ready to use XML sitemap that can be
included via `Site sets <https://docs.typo3.org/permalink/typo3/cms-seo:configuration-site-sets>`_
(recommended) or include in your TypoScript record.

.. contents:: Table of Contents
   :depth: 1
   :local:

..  _xmlsitemap-url:

How to access your XML sitemap
==============================

You can access the sitemaps by visiting `https://example.org/?type=1533906435`.
You will first see the sitemap index. By default, there is one sitemap in the
index. This is the sitemap for pages.

..  note::
    Each siteroot and language configured in the
    `Site handling <https://docs.typo3.org/permalink/t3coreapi:sitehandling>`_
    has its own XML sitemap depending on the entry point.

    **Example:**

    -   Entry point `/` - :samp:`https://example.org/?type=1533906435`: for default language
    -   Entry point `/fr/` - :samp:`https://example.org/fr/?type=1533906435`: for French
    -   Entry point `/it/` - :samp:`https://example.org/it/?type=1533906435`: for Italian

..  _xmlsitemap-routing:

How to setup routing for the XML sitemap
========================================

You can use the `PageType decorator <https://docs.typo3.org/permalink/t3coreapi:routing-pagetype-decorator>`_
to map page types to a fixed suffix. This allows you to expose the sitemap with a
readable URL, for example :samp:`https://example.org/sitemap.xml`.

Additionally, you can map the parameter `sitemap`, so that the links to the
different sitemap types (`pages` and additional ones, for example, from the
news extension) are also mapped.

..  literalinclude:: _xmlSitemap/_config.yaml
    :caption: config/sites/<your_site>/config.yaml

.. index:: XmlSitemapDataProviders


..  _xmlsitemap-data-providers:

Data providers for XML sitemaps
===============================

The rendering of sitemaps is based on data providers implementing
:php:`\TYPO3\CMS\Seo\XmlSitemap\XmlSitemapDataProviderInterface.

:composer:`typo3/cms-seo` ships with the following data providers for XML
sitemaps:

..  _xmlsitemap-data-providers-pages:

For pages: PagesXmlSitemapDataProvider
--------------------------------------

The :php:`\TYPO3\CMS\Seo\XmlSitemap\PagesXmlSitemapDataProvider` will generate a
sitemap of pages based on the detected site root. You can configure whether you
have additional conditions for selecting the pages.

Via setting :ref:`seo.sitemap.pages.excludedDoktypes <typo3/cms-seo:confval-seo-settings-seo-sitemap-pages-excludeddoktypes>`
it is possible to exclude certain `Types of pages <https://docs.typo3.org/permalink/t3coreapi:list-of-page-types>`_.

Additionally, you may exclude page subtrees from the sitemap
(for example internal pages). This can be
configured using setting
:ref:`seo.sitemap.pages.excludePagesRecursive <typo3/cms-seo:confval-seo-settings-seo-sitemap-pages-excludepagesrecursive>`.

If your site still depend on TypoScript records instead of site sets, you can
make these settings via TypoScript constants.

For special use cases you might want to override the default TypoScript provided
by the set.

..  _xmlsitemap-data-providers-records:

For database records: RecordsXmlSitemapDataProvider
---------------------------------------------------

If you have an extension installed and want a sitemap of those records, the
:php:`\TYPO3\CMS\Seo\XmlSitemap\RecordsXmlSitemapDataProvider` can be used. The
following example shows how to add a sitemap for news records:

..  literalinclude:: _xmlSitemap/_record.typoscript
    :caption: EXT:my_extension/Configuration/Sets/XmlSitemapNews/setup.typoscript

You can add multiple sitemaps and they will be added to the sitemap index
automatically. Use different types to have multiple, independent sitemaps:

..  literalinclude:: _xmlSitemap/_multiple.typoscript
    :caption: EXT:my_extension/Configuration/Sets/XmlSitemapMultiple/setup.typoscript

..  _xmlsitemap-changefreq-priority:

Change frequency and priority
=============================

Change frequencies define how often each page is approximately updated and hence
how often it should be revisited (for example: News in an archive are "never"
updated, while your home page might get "weekly" updates).

Priority allows you to define how important the page is compared to other pages
on your site. The priority is stated in a value from 0 to 1. Your most important
pages can get an higher priority as other pages. This value does not affect how
important your pages are compared to pages of other websites. All pages and
records get a priority of 0.5 by default.

The settings can be defined in the TypoScript configuration of an XML sitemap by
mapping the properties to fields of the record by using the options
:typoscript:`changeFreqField` and :typoscript:`priorityField`.
:typoscript:`changeFreqField` needs to point to a field containing string values
(see :typoscript:`pages` TCA definition of field
:typoscript:`sitemap_changefreq`), :typoscript:`priorityField` needs to point to
a field with a decimal value between 0 and 1.

..  note::
    Both the priority and the change frequency have no impact on your rankings.
    These options only give hints to search engines in which order and how often
    you would like a crawler to visit your pages.

..  _xmlsitemap-without-sorting:

Sitemap of records without sorting field
========================================

Sitemaps are paginated by default. To ensure that as few pages of the sitemap
as possible are changed after the number of records is changed, the items in the
sitemaps are ordered. By default, this is done using a sorting field. If you do
not have such a field, make sure to configure this in your sitemap configuration
and use a different field. An example you can use for sorting based on the uid
field:

..  literalinclude:: _xmlSitemap/_recordUnsorted.typoscript
    :caption: EXT:my_extension/Configuration/Sets/XmlSitemapTableWithoutSorting/setup.typoscript

..  _xmlsitemap-custom-provider:

Create a custom XML sitemap provider
====================================

If you need more logic in your sitemap, you can also write your own
sitemap provider. You can do this by extending the
:php:`\TYPO3\CMS\Seo\XmlSitemap\AbstractXmlSitemapDataProvider` class or
implementing :php:`\TYPO3\CMS\Seo\XmlSitemap\RecordsXmlSitemapDataProvider`.

The main methods of interest are :php:`getLastModified()` and :php:`getItems()`.

The :php:`getLastModified()` method is used in the sitemap index and has to
return the date of the last modified item in the sitemap.

The :php:`getItems()` method has to return an array with the items for the
sitemap:

..  code-block:: php
    :caption: EXT:my_extension/Classes/XmlSitemap/MyXmlSitemapProvider.php

    $this->items[] = [
        'loc' => 'https://example.org/page1.html',
        'lastMod' => '1536003609'
    ];

The :php:`loc` element is the URL of the page to be crawled by a search engine.
The :php:`lastMod` element contains the date of the last update of the
specific item. This value is a UNIX timestamp. In addition, you can include
:php:`changefreq` and :php:`priority` as keys in the array to give
:ref:`search engines a hint <xmlsitemap-changefreq-priority>`.

.. _sitemap-xslFile:

Use a customized sitemap XSL file
=================================

The XSL file used to create a layout for an XML sitemap can be configured at
three levels:

#.  For all sitemaps:

    ..  code-block:: typoscript
        :caption: EXT:my_extension/Configuration/TypoScript/setup.typoscript

        plugin.tx_seo.config {
            xslFile = EXT:my_extension/Resources/Public/CSS/mySite.xsl
        }

#.  For all sitemaps of a certain sitemapType:

    ..  code-block:: typoscript
        :caption: EXT:my_extension/Configuration/TypoScript/setup.typoscript

        plugin.tx_seo.config.mySitemapType.sitemaps {
            xslFile = EXT:my_extension/Resources/Public/CSS/mySite.xsl
        }

#.  For a specific sitemap:

    ..  code-block:: typoscript
        :caption: EXT:my_extension/Configuration/TypoScript/setup.typoscript

        plugin.tx_seo.config.xmlSitemap.sitemaps.myNewsSitemap.config {
            xslFile = EXT:my_extension/Resources/Public/CSS/mySite.xsl
        }

The value is inherited until it is overwritten.

If no value is specified at all, :file:`EXT:seo/Resources/Public/CSS/Sitemap.xsl`
is used as default.
