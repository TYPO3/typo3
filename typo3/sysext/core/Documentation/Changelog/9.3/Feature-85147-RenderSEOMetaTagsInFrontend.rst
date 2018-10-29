.. include:: ../../Includes.txt

==================================================
Feature: #85147 - Render SEO meta tags in frontend
==================================================

See :issue:`85147`

Description
===========

The SEO meta tags that can be set in the page properties, are now rendered in frontend by default if the system extension
SEO is installed.

The og:image and twitter:image will be rendered in the 1.91:1 aspect ratio by default. This ratio is used by most of the
social networks when showing an image of the shared link. You can also choose a free ratio or add ratio's yourself.

Impact
======

No additional configuration is needed to render these meta tags. If you want to override the meta tags set by
the page properties, you can use the replace parameter in TypoScript or in the addProperty method of the specific
MetaTagManager.

.. index:: Frontend, ext:seo
