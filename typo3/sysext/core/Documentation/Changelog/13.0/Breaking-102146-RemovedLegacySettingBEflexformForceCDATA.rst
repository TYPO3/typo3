.. include:: /Includes.rst.txt

.. _breaking-102146-1697045119:

==================================================================
Breaking: #102146 - Removed legacy setting 'BE/flexformForceCDATA'
==================================================================

See :issue:`102146`

Description
===========

The TYPO3 configuration option :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['flexformForceCDATA']`
has been removed without substitution.

This setting was an ancient workaround for an issue in libxml in old PHP versions that has
been resolved long ago.

This was the last usage of :php:`useCDATA` option in FlexForm-related XML methods in
the Core, so that option is removed along the way. Values of XML data should still be
encoded properly when dealing with related methods like :php:`GeneralUtility::array2xml()`.


Impact
======

There should be no impact on casual instances, except if single extensions tamper with
the :php:`useCDATA` options when dealing with XML data.


Affected installations
======================

Instances with extensions that explicitly call XML-related transformations methods
provided by the Core that tamper with :php:`useCDATA` may need a look. Chances are
everything is ok, though.


Migration
=========

No direct migration possible.

.. index:: LocalConfiguration, PartiallyScanned, ext:core
