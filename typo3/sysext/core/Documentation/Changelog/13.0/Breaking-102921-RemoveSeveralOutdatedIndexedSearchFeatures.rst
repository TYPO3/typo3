.. include:: /Includes.rst.txt

.. _breaking-102921-1706170368:

===================================================================
Breaking: #102921 - Remove several outdated indexed search features
===================================================================

See :issue:`102921`

Description
===========

The internal search of TYPO3, Indexed Search exists since over 20 years. Some
functionality that is shipped with the search form is not considered up-to-date
anymore, in regard to templating, as Indexed Search has an Extbase and
Fluid-based plugin since TYPO3 v6.2 (10 years).

Some functionality was never removed, which is now the case:

* The ability to customize the styling of a specific page via
  :typoscript:`plugin.tx_indexedsearch.settings.specialConfiguration`
* The ability to customize a result icon (used as Gif images) based on the type
  via :typoscript:`plugin.tx_indexedsearch.settings.iconRendering`
* The ability to customize a result language symbol icon (used as Gif images)
  based on the page language via :typoscript:`plugin.tx_indexedsearch.settings.flagRendering`

In addition, the possibility for visitors to change only search for results in
a language other than the current language is removed. It proved little sense
to search for e.g. Japanese content on a French websites.


Impact
======

All of the TypoScript settings are not evaluated anymore. The Fluid variables
:html:`{allLanguageUids}`, :html:`{row.language}` and :html:`{row.icon}` are not
filled anymore.

Search only shows results in the language of the currently active language
of the website.


Affected installations
======================

TYPO3 installations using these options or features with Indexed Search.


Migration
=========

Adapt your TypoScript settings, and remove the TypoScript settings and Fluid
variables.

If you still need specific rendering of icons for pages, or customized CSS for
result pages, it is recommended to use Fluid conditions adapted in your custom
template, which is usually not necessary.

.. index:: Frontend, TypoScript, NotScanned, ext:indexed_search
