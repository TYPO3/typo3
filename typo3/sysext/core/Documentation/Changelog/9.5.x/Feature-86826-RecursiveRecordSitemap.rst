.. include:: ../../Includes.txt

==========================================
Feature: #86826 - Recursive record sitemap
==========================================

See :issue:`86826`

Description
===========

The :php:`RecordsXmlSitemapDataProvider` supports now the configuration `recursive` to include
records not only from provided list of page ids but also its subpages.


Impact
======

A full example looks is:

.. code-block:: typoscript

  config {
    xmlSitemap {
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

.. index:: Frontend, ext:seo, NotScanned