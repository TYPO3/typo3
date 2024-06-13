.. include:: /Includes.rst.txt

.. _important-101621-1718125029:

=================================================================
Important: #101621 - Changed default value for twitter_card field
=================================================================

See :issue:`101621`

Description
===========

The default value of the field `twitter_card` of a page is now an empty string instead of `summary`.

Only if one of the following fields is filled, the meta tag :html:`<meta name="twitter:card">` is rendered.

- `twitter_title`
- `twitter_description`
- `twitter_image`
- `twitter_card`
- `og_title`
- `og_description`
- `og_image`

If no twitter card is selected, the fallback value is `summary`.

.. index:: Frontend, ext:seo
