.. include:: /Includes.rst.txt

.. _breaking-102900-1706016651:

=================================================================
Breaking: #102900 - Metaphone search removed from indexed\_search
=================================================================

See :issue:`102900`

Description
===========

The indexed_search based frontend functionality had a feature called "metaphone"
to look for matches that "sound similar" to the given search word. This was available
using the "Advanced search" interface, if it has not been disabled by an
integrator. This feature has been removed.

This feature had so many issues that it was deemed unfixable:

* Most importantly, search results were bad. Even during dedicated testing, it
  was hard to retrieve any "similar sounding" results.
* The implementation was tailored for English language only, lacking support for
  any non-ASCII characters like umlauts. Sites with languages not based on
  single byte characters got even worse results.
* The code has been not maintained for about 15 years.
* The feature seems to be used so seldom, there does not seem to be a single
  extension that tries to fix at least the most important issues.
* There has been no issues reported about this broken feature over the years,
  except when it triggered crashes.

All in all it seems as if that feature was used extremely seldom, most likely
because the results are so bad.

On a code level, the removal affects these areas:

* Class :php:`\TYPO3\CMS\IndexedSearch\Utility\DoubleMetaPhoneUtility` has been
  removed.
* The "hook" :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['metaphone']`
  to register an own "metaphone" solution has been removed.
* The extension configuration option :php:`enableMetaphoneSearch` has been removed.
* The database columns :sql:`index_fulltext.metaphonedata` and :sql:`index_words.metaphone`
  have been removed.
* A couple of methods and properties in the :php:`@internal` marked indexed_search
  classes have been removed and simplified.


Impact
======

Frontend users can no longer select the "Sounds like" option when searching the
website. Backend users do not see statistics about this search variant in the
backend module.


Affected installations
======================

Websites with a search solution based on indexed_search with "metaphone" search
being active in the extension configuration, and with users actively using
the "metaphone" search feature.


Migration
=========

No migration available. Sites that really need this feature should switch to
a more sophisticated search solution.

.. index:: Backend, Database, Frontend, PHP-API, FullyScanned, ext:indexed_search
