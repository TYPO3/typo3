.. include:: /Includes.rst.txt

=============================================================
Feature: #87433 - Add changefreq and priority for XML sitemap
=============================================================

See :issue:`87433`

Description
===========

Sitemap.xml files may contain a change frequency and a priority for entries.

Change frequencies define how often each page is approximately updated and hence how often it should be
revisited (for example: News in an archive are "never" updated, while your home page might get "weekly" updates).

Priority allows you to define how important the page is compared to other pages on your site. The priority is stated
in a value from 0 to 1. Your most important pages can get an higher priority as other pages. This value does not
affect how important your pages are compared to pages of other websites.

This feature allows to define the properties :typoscript:`changefreq` and :typoscript:`priority` for sitemap entries in TYPO3.

The properties :typoscript:`changefreq` and :typoscript:`priority` of pages can be controlled via page properties.
For records, the settings can be defined in TypoScript by mapping the properties to fields of the record by
using the options :typoscript:`changeFreqField` and :typoscript:`priorityField`. :typoscript:`changeFreqField` needs to point to a field containing
string values (see :typoscript:`pages` definition of field :typoscript:`sitemap_changefreq`), :typoscript:`priorityField` needs to point to a field with
a decimal value between 0 and 1.


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
                     changeFreqField = news_changefreq
                     priorityField = news_priority
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
                     }
                  }
               }
            }
         }
      }
   }


Impact
======

Two new fields are available in the page properties: `sitemap_priority` (decimal) and `sitemap_changefreq` (list of values, for example "weekly", "daily", "never").

Two new TypoScript options for the :typoscript:`RecordsXmlSitemapDataProvider` have been introduced:
:typoscript:`changeFreqField` and :typoscript:`priorityField`.

All pages and records get a priority of 0.5 by default.

.. attention::

   Both priority and change frequency does have no impact on your rankings. These options only gives hints to search engines
   in which order and how often you would like a crawler to visit your pages.

.. index:: ext:seo, Frontend, TypoScript
