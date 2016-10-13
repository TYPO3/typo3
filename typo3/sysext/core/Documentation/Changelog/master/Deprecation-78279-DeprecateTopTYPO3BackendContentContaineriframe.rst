.. include:: ../../Includes.txt

=========================================================================
Deprecation: #78279 - Deprecate top.TYPO3.Backend.ContentContainer.iframe
=========================================================================

See :forge:`78279`

Description
===========

The property :js:`top.TYPO3.Backend.ContentContainer.iframe` has been deprecated.


Impact
======

Usage of this property will stop work with TYPO3 v9


Affected Installations
======================

All installations using :js:`top.TYPO3.Backend.ContentContainer.iframe`.


Migration
=========

Use accessor method :js:`top.TYPO3.Backend.ContentContainer.get()` instead.
