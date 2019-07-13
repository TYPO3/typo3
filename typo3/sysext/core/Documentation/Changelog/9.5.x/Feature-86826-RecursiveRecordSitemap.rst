.. include:: ../../Includes.txt

==========================================
Feature: #86826 - Recursive record sitemap
==========================================

See :issue:`86826`

Description
===========

The class :php:`TYPO3\CMS\Seo\XmlSitemap\RecordsXmlSitemapDataProvider` supports now the configuration `recursive` to include
records not only from provided list of page ids but also its subpages.
:typoscript:`recursive` refers to the number of levels taken into account beyond the `pid` page. (default: 0)

Impact
======

A full example:

.. code-block:: typoscript

  plugin.tx_seo {
    config {
      xmlSitemap {
         sitemaps {
            news {
               provider = TYPO3\CMS\Seo\XmlSitemap\RecordsXmlSitemapDataProvider
               config {
                  table = tx_news_domain_model_news
                  sortField = sorting
                  lastModifiedField = tstamp
                  pid = 26
                  recursive = 2
                  url {
                     pageId = 25
                     fieldToParameterMap {
                        uid = tx_news_pi1[news]
                     }
                     additionalGetParameters {
                        tx_news_pi1.controller = News
                        tx_news_pi1.action = detail
                     }
                     useCacheHash = 1
                  }
               }
            }
         }
      }
   }


.. index:: Frontend, ext:seo
