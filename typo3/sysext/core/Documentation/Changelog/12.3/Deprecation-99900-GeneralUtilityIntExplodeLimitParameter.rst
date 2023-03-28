.. include:: /Includes.rst.txt

.. _deprecation-99900-1676292952:

======================================================================
Deprecation: #99900 - $limit parameter of GeneralUtility::intExplode()
======================================================================

See :issue:`99900`

Description
===========

The static method :php:`GeneralUtility::intExplode()` has a lesser known fourth
parameter :php:`$limit`. The reason it was added to the :php:`intExplode()` method
is purely historical, when it used to extend the :php:`trimExplode()` method. The
dependency was resolved, but the parameter stayed. As this method is supposed to
only return :php:`int` values in an array, the :php:`$limit` parameter is now
deprecated.

Impact
======

Calling :php:`GeneralUtility::intExplode()` with the fourth parameter
:php:`$limit` will trigger a deprecation warning and will add an entry to the
deprecation log.

Affected installations
======================

TYPO3 installations that call :php:`GeneralUtility::intExplode()` with the
fourth parameter :php:`$limit`.

Migration
=========

In the rare case that you are using the :php:`$limit` parameter you will need to
switch to PHP's native :php:`explode()` function, and then use
:php:`array_map()` to convert the resulting array to integers. If that's
impractical, you can simply copy the old :php:`intExplode` method to your own
code.

.. index:: PHP-API, FullyScanned, ext:core
