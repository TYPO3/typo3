:navigation-title: Features

..  include:: /Includes.rst.txt
..  _features:

============================================
Features of the TYPO3 system extension "seo"
============================================

The TYPO3 system extension :composer:`typo3/cms-seo` offers multiple tools and
fields that can be used to improve visibility of a TYPO3 site in search engines:

..  contents::

..  toctree::
    :glob:
    :hidden:

    *

..  _seo-page-properties:

Additional tabs "SEO" and "Social media" in the page properties
===============================================================

After `Installation <https://docs.typo3.org/permalink/typo3-cms-seo:installation>`_
two additional tabs are available in the page properties.

SEO
    This tab contains additional fields for the
    `title tag <https://docs.typo3.org/permalink/typo3-cms-seo:seo-page-title-provider>`_
    in the HTML header, for the description meta tag, for robots instruction, the
    `Canonical URL <https://docs.typo3.org/permalink/typo3-cms-seo:canonical-url>`_
    and for priorities used in the
    `XML Sitemap <https://docs.typo3.org/permalink/typo3-cms-seo:xml-sitemap>`_.
Social media
    This tab contains additional fields to manage data for the
    Open Graph (Facebook) meta tags and the X / Twitter Cards.

Usage of these additional fields is described in the Editors Tutorial,
`Search engine optimization (SEO) for TYPO3 editors <https://docs.typo3.org/permalink/t3editors:seo>`_.

..  _seo-page-dashboard:

A Dashboard Widget for SEO
==========================

The extension also offers an additional Dashboard widget.
:composer:`typo3/cms-dashboard` needs to be installed. Usage is described in
the Editors Tutorial, chapter
`Dashboard widgets for Search engine optimization (SEO) in TYPO3 <https://docs.typo3.org/permalink/t3editors:dashboard-widgets>`_.

If your editors have one of the standard user groups "Editor" or "Advanced Editor",
created by the command `typo3 setup:begroups:default` they have permissions to
use the widget.

If you created the use groups manually you users need to have "Dashboard" in
their allowed modules and "Pages missing Meta Description" in the
allowed dashboard widgets list:
`Dashboard manual, permissions of widgets <https://docs.typo3.org/permalink/typo3-cms-dashboard:permission-handling-of-widgets>`_

..  _xml-sitemap:

XML Sitemap
===========

The extension :composer:`typo3/cms-seo` comes with the site set
`typo3/seo-sitemap <https://docs.typo3.org/permalink/typo3-cms-seo:configuration-site-sets>`_,
which you can use to provide an XML sitemap like `https://example.org/sitemap.xml`.

See chapter `XML sitemap <https://docs.typo3.org/permalink/typo3-cms-seo:xmlsitemap>`_
for details.

..  _canonical-url:

Canonical URL
=============

When :composer:`typo3/cms-seo` is installed, pages automatically contain a
canonical link tag in their HTML head, unless disabled via TypoScript.

..  code-block:: html
    :caption: example output of a canonical link in the head of a TYPO3 page

    <head>

    <!-- ... -->

    <link rel="canonical" href="https://example.org/somepage"/>
    </head>

You can use the event `ModifyUrlForCanonicalTagEvent <https://docs.typo3.org/permalink/t3coreapi:modifyurlforcanonicaltagevent>`_
to provide an alternative canonical URL if needed.

The API of the canonical link is described in
`Canonical API, TYPO3 Explained <https://docs.typo3.org/permalink/t3coreapi:canonicalapi>`_.

..  warning::
    If you have other SEO extensions installed that generate canonical links,
    you have to make sure only one is responsible to embed into your frontend
    output.

    If both the Core and another extension are generating a canonical link,
    it will result in 2 canonical links which might cause confusion for search
    engines.

..  _seo-page-title-provider:

SEO page title provider
=======================

While the `Page title API <https://docs.typo3.org/permalink/t3coreapi:pagetitle>`_,
providing a `<title>` tag in the HTML head is part of a minimal TYPO3 installation,
:composer:`typo3/cms-seo` provides an additional field, `seo_title` in the page
properties. The :php:`\TYPO3\CMS\Seo\PageTitle\SeoTitlePageTitleProvider`
provides this title as an alternative title for the `<title>` tag.

The following default TypoScript setup is provided for the page title provider:

..  code-block:: typoscript

    config.pageTitleProviders {
        seo {
            provider = TYPO3\CMS\Seo\PageTitle\SeoTitlePageTitleProvider
            before = record
        }
    }

..  _seo-meta-tag-provider:

Additional meta tag handling
============================

While the `MetaTag API <https://docs.typo3.org/permalink/t3coreapi:metatagapi>`_
is part of the minimal TYPO3 Core, the meta tag providers for the description
meta tag commonly used for search engine optimazation, and the social preview
meta tags of Open Graph and Twitter / X are part of :composer:`typo3/cms-seo`.

..  _seo-hreflang:

Hreflang tags
=============

:html:`hreflang` link-tags are added automatically for multi-language websites
based on the one-tree principle.

The links are based on the site configuration and depend on translations of a page.

:html:`hreflang="x-default"` indicates the link of the current page in the default language.

The value of :html:`hreflang` is set for each language in
:guilabel:`Site Management > Sites` (see :ref:`t3coreapi:sitehandling-addingLanguages`)

