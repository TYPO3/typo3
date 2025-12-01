..  include:: /Includes.rst.txt

..  _feature-107837-1732800001:

=============================================================
Feature: #107837 - Sitemap route enhancers provided via site set
=============================================================

See :issue:`107837`

Description
===========

The SEO extension now ships its sitemap route enhancers as part of
the `typo3/seo-sitemap` site set. When this set is used as a dependency,
the route enhancers for XML sitemaps are automatically configured.

This enables clean URLs for sitemaps out of the box:

- `/sitemap.xml` - Main sitemap index
- `/sitemap-type/pages` - Pages sitemap

Previously, these route enhancers had to be manually configured in each
site's `config.yaml`.

Impact
======

Sites using the `typo3/seo-sitemap` set no longer need to manually
configure sitemap route enhancers. The clean URLs are available
automatically.

Sites can still override or extend the route enhancers in their
site configuration if needed.

..  index:: Frontend, YAML, ext:seo
