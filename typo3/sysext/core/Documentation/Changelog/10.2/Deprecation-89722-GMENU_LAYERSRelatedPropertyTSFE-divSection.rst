.. include:: /Includes.rst.txt

====================================================================
Deprecation: #89722 - GMENU_LAYERS related property TSFE->divSection
====================================================================

See :issue:`89722`

Description
===========

The public PHP property :php:`TypoScriptFrontendController->divSection` has been marked as deprecated. This was used in prior
TYPO3 versions to add dynamic JavaScript related to GMENU_LAYERS
functionality which was removed with previous TYPO3 versions, making
this property only produce unnecessary overhead in frontend rendering.


Impact
======

Accessing or setting this property will trigger a deprecation notice.


Affected Installations
======================

TYPO3 installations with extensions explicitly accessing this property, which is highly unlikely as this property is very lowlevel.


Migration
=========

If there is a need to add JavaScript within uncached content, use
:php:`$GLOBALS['TSFE']->additionalHeaderData[]` instead.

.. index:: Frontend, FullyScanned, ext:frontend
