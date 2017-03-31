.. include:: ../../Includes.txt

==========================================================================
Breaking: #80050 - Remove option cHashIncludePageId from cHash calculation
==========================================================================

See :issue:`80050`

Description
===========

The global configuration option :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['cHashIncludePageId']`
has been removed, as the functionality is now always activated.

The option was introduced in 2016 as part of a security bugfix for existing releases to
allow the inclusion the cHash calculation (= caching identifier for pages with different GET variables)
and was active for new installations.


Impact
======

Setting the option has no effect anymore.

If the option was disabled before, all existing cached contents and existing cHash calculations for URL
rewrites (e.g. RealURL) of existing pages are invalidated and will throw a "page not found" exception
if called directly.


Affected Installations
======================

Any existing TYPO3 installation that did not have the option activated before.

.. index:: Frontend, LocalConfiguration
