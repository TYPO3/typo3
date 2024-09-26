.. include:: /Includes.rst.txt

.. _important-101621-1718125029:

=================================================================
Important: #101621 - Changed default value for twitter_card field
=================================================================

See :issue:`101621`

Description
===========

The default value of the `twitter_card` field of a page is now an empty string
instead of `summary`.

Meta tag :html:`<meta name="twitter:card">` is only rendered if one of the
following fields is filled in,

- `twitter_title`
- `twitter_description`
- `twitter_image`
- `twitter_card`
- `og_title`
- `og_description`
- `og_image`

If no twitter card is selected, the fallback value is `summary`.

.. index:: Frontend, ext:seo
