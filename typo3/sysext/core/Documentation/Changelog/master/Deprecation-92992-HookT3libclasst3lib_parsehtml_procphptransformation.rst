.. include:: ../../Includes.txt

==================================================================
Deprecation: #92992 - Hook t3lib_parsehtml_proc.php:transformation
==================================================================

See :issue:`92992`

Description
===========

Since the deprecation of several internal functions in the RteHtmlParser in
TYPO3 10.2 (
.. _linked: https://docs.typo3.org/c/typo3/cms-core/10.2/en-us/Changelog/9.5/Deprecation-86440-InternalMethodsAndPropertiesWithinRteHtmlParser.html)
the hook :php:`t3lib/class.t3lib_parsehtml_proc.php:transformation` became quite useless.

It is now impossible to access the tsconfig configuration and other information
that you would need for examples that we had the documentation like here:
.. _linked: https://docs.typo3.org/m/typo3/reference-coreapi/master/en-us/ApiOverview/Rte/Transformations/CustomApi.html


Impact
======

Calling the hook will trigger a PHP :php:`E_USER_DEPRECATED` error and not be executed anymore with TYPO3 v12.


Affected Installations
======================

All installations with extensions installed that implement the hook.


Migration
=========

Migrate to use the public API only and use other options (such as
:php:`allowAttributes` instead of :php:`dontRemoveUnknownTags_db`) in order to
only run certain instructions on the :php:`RteHtmlParser` object.

.. index:: RTE, NotScanned, ext:core
