.. include:: /Includes.rst.txt

=========================================================
Feature: #86057 - Improved typolink / URL link generation
=========================================================

See :issue:`86057`

Description
===========

With new site-based handling, the de-facto standard GET parameter "L" (for language) became
obsolete.

Instead, in order to create a link to a specific language via TypoScript's :typoscript:`typolink` functionality,
a new parameter :typoscript:`typolink.language` is introduced.

.. code-block:: typoscript

    page.10 = TEXT
    page.10.value = Link to the page with the ID in the current language
    page.10.typolink.parameter = 23
    page.20 = TEXT
    page.20.value = Link to the page with the ID in the language 3
    page.20.typolink.parameter = 23
    page.20.typolink.language = 3

Omitting the parameter :typoscript:`language` will use the current language.
If a page is not available in the requested language, the link will not be generated,
however a fallback to the default language can be built, as the HMENU TypoScript functionality does.

Due to the new page-linking functionality, the following TypoScript settings are not necessary anymore
and should be removed for TypoScript configurations on page trees with a site configuration:

* Including the "L" parameter in :typoscript:`config.linkVars`, as the L parameter is not evaluated for
  page requests with a site configuration.
* :typoscript:`config.absRefPrefix` is only necessary for links to files or images, but not for
  page links, as they are always built against the absolute path, or - if :typoscript:`typolink.forceAbsoluteUrl`
  is explicitly set.
  The option is set to :typoscript:`auto` by default for site configuration TypoScripts, so this is not necessary
  anymore in regular installations.
* One of the major strengths allows to link across sites / domains with specifically knowing
  all available languages of a different page tree. Using the :typoscript:`config.typolinkEnableLinksAcrossDomains`
  is not necessary anymore for TypoScript within a site configuration.


Impact
======

When using :typoscript:`typolink.additionalParams = &L=1`, this is automatically mapped to
the :typoscript:`typolink.language` parameter, but if both are set, the :typoscript:`typolink.language` option
takes precedence.

When generating links with `&L=` query parameters, this parameter is stripped, and the correct base
URL for the site is fetched, and the query parameter is not added anymore.

By setting the target page ID via :typoscript:`typolink.parameter` it is also possible to set the
page ID to a localized page ID, automatically resolving to the correct language if neither
:typoscript:`typolink.parameter` nor :typoscript:`typolink.additionalParams = L=` is set.

.. index:: TypoScript, ext:frontend
