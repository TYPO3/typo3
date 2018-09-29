.. include:: ../../Includes.txt

=============================================
Feature: #20051 - Support the "canonical" tag
=============================================

See :issue:`20051`

Description
===========

TYPO3 will finally provide built-in support for the :html:`<link rel="canonical" href="">` tag.

If the core extension "seo" is installed, it will automatically add the canonical link to the page.

The canonical link is basically the same absolute link as the link to the current hreflang and is meant
to indicate where the original source of the content is. It is a tool to prevent duplicate content
penalties.

In the page properties, the canonical link can be overwritten per language. The link wizard offers all
possibilities including external links and link handler configurations.

Should an empty href occur when generating the link to overwrite the canonical (this happens e.g. if the
selected page is not available in the current language), the fallback to the current hreflang will be activated
automatically. This ensures that there is no empty canonical.

Impact
======

If you have other SEO extensions installed that generate canonical links, you have to make sure only one creates it.
If both core and an extension are generating a canonical link, it will
result in 2 canonical links which might cause confusion for search engines.

.. index:: Backend, Database, Frontend, TCA, ext:seo
