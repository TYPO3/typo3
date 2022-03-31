.. include:: /Includes.rst.txt

.. highlight:: typoscript

==========================================================================
Feature: #88147 - Add possibility to configure the path to sitemap xslFile
==========================================================================

See :issue:`88147`

Description
===========

The xsl file to create a layout for a XML sitemap can now be configured on three levels:

1. for all sitemaps::

      plugin.tx_seo.config.xslFile = EXT:myext/Resources/Public/CSS/mySite.xsl

2. for all sitemaps of a certain sitemapType::

      plugin.tx_seo.config.<sitemapType>.sitemaps.xslFile = EXT:myext/Resources/Public/CSS/mySite.xsl

3. for a specific sitemap::

      plugin.tx_seo.config.<sitemapType>.sitemaps.<sitemap>.config.xslFile = EXT:myext/Resources/Public/CSS/mySite.xsl

Impact
======

The value is inherited until it is overwritten.

If no value is specified at all, :file:`EXT:seo/Resources/Public/CSS/Sitemap.xsl` is used as default like before.

.. index:: Frontend, TypoScript, ext:seo
