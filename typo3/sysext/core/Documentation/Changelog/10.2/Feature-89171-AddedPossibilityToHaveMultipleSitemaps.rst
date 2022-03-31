.. include:: /Includes.rst.txt

=============================================================
Feature: #89171 - Added possibility to have multiple sitemaps
=============================================================

See :issue:`89171`

Description
===========

You can now also create multiple different sitemaps. This can be handy for situations, where
different target systems need them in different format or order. (e.g. Google News Sitemaps)

The syntax looks like this:

.. code-block:: typoscript

   plugin.tx_seo {
      config {
         <sitemapType> {
            sitemaps {
               <unique key> {
                  provider = TYPO3\CMS\Seo\XmlSitemap\RecordsXmlSitemapDataProvider
                  config {
                     ...
                  }
               }
            }
         }
      }
   }

Example:

.. code-block:: typoscript

   seo_googlenews < seo_sitemap
   seo_googlenews.typeNum = 1571859552
   seo_googlenews.10.sitemapType = googleNewsSitemap

   plugin.tx_seo {
       config {
           xmlSitemap {
               sitemaps {
                   news {
                       provider = GeorgRinger\News\Seo\NewsXmlSitemapDataProvider
                       config {
                           ...
                       }
                   }
               }
           }
           googleNewsSitemap {
               sitemaps {
                   news {
                       provider = GeorgRinger\News\Seo\NewsXmlSitemapDataProvider
                       config {
                           googleNews = 1
                           ...
                           template = GoogleNewsXmlSitemap.html
                       }
                   }
               }
           }
       }
   }



Impact
======

As it only gives the possibility to add multiple sitemaps, it won't affect any installation unless you add more sitemaps
yourself.

.. index:: ext:seo
