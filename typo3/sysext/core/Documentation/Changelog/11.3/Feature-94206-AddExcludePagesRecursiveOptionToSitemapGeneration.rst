.. include:: /Includes.rst.txt

============================================================================
Feature: #94206 - Add excludePagesRecursive option to XML sitemap generation
============================================================================

See :issue:`94206`

Description
===========

With this option you can exclude pages recursively in the XML sitemap:

.. code-block:: typoscript

   plugin.tx_seo {
       config {
           xmlSitemap {
               sitemaps {
                   pages {
                       config {
                           # comma-separated list of page UIDs which should be excluded recursively
                           excludePagesRecursive = 2,3
                       }
                   }
               }
           }
       }
   }

Impact
======

The new option enables integrators to easily exclude pages recursively in the XML sitemap

.. index:: ext:seo
