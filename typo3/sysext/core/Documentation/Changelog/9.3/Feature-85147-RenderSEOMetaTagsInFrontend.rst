.. include:: ../../Includes.txt

==================================================
Feature: #85147 - Render SEO meta tags in frontend
==================================================

See :issue:`85147`

Description
===========

The SEO meta tags that can be set in the page properties, are now rendered in frontend by default if the system extension
SEO is installed.


Impact
======

No additional configuration is needed to render these meta tags. If you want to override the meta tags set by
the page properties, you can use the replace parameter in TypoScript or in the addProperty method of the specific
MetaTagManager.

.. index:: Frontend, ext:seo