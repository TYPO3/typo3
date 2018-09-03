.. include:: ../../Includes.txt

=============================
Feature: #84525 - XML Sitemap
=============================

See :issue:`84525`

Description
===========

It is now possible to generate XML sitemaps for SEO purposes without using 3rd-party plugins.
When enabled, this new feature will create a sitemap index with one or more sitemaps in it.
Out-of-the-box it will have one sitemap containing all the pages of the current site and
language. Per site and per language you have the possibility to render a different sitemap.

Installation
------------

The XML sitemap is disabled by default. You can easily enable it by installing the system
extension "seo" and including the static TypoScript template "XML Sitemap (seo)". It is also
mandatory to have a site configuration for your rootpage(s).

How to access your XML sitemap
------------------------------

Until it is possible to have a default route with the new URL handling mechanism, you can access
the sitemaps by calling https://yourdomain.com/?type=1533906435 in your browser. You will first see the sitemap
index. By default you will see one sitemap in the index. This is the sitemap for pages.

If you have multiple siteroots or multiple languages with different domains or language prefixes,
you can just go to the domain that handles the siteroot / language. The sitemap will be based on
the settings for that domain.

XmlSitemapDataProviders
-----------------------

The rendering of sitemaps is based on XmlSitemapDataProviders. EXT:seo extension ships with two
XmlSitemapDataProviders. The first one is the :php:`PagesXmlSitemapDataProvider`. This will generate a sitemap
of pages based on the siteroot that is detected. You can configure if you have additional conditions
for the selection of pages. You also have the possibility to exclude certain doktypes.

.. code-block:: typoscript

   plugin.tx_seo {
      config {
         xmlSitemap {
            sitemaps {
               pages {
                  config {
                     excludedDoktypes = 137, 138
                     additionalWhere = AND (no_index = 0 OR no_follow = 0)
                  }
               }
            }
         }
      }
   }

If you also have an extension installed and want a sitemap of those records, you can use the
:php:`RecordsXmlSitemapDataProvider`. You can add for example a sitemap for news records:

.. code-block:: typoscript

   plugin.tx_seo {
      config {
         xmlSitemap {
            sitemaps {
               <unique key> {
                  provider = TYPO3\CMS\Seo\XmlSitemap\RecordsXmlSitemapDataProvider
                  config {
                     table = news_table
                     sortField = sorting
                     lastModifiedField = tstamp
                     additionalWhere = AND (no_index = 0 OR no_follow = 0)
                     pid = <page id('s) containing news records>
                     url {
                        pageId = <your detail page id>
                        fieldToParameterMap {
                           uid = tx_extension_pi1[news]
                        }
                        additionalGetParameters {
                           tx_extension_pi1.controller = News
                           tx_extension_pi1.action = detail
                        }
                        useCacheHash = 1
                     }
                  }
               }
            }
         }
      }
   }

You can add several sitemaps and those will be added to the sitemap index automatically.

.. index:: Frontend, ext:seo
