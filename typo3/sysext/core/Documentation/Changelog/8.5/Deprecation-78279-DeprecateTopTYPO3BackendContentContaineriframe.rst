.. include:: /Includes.rst.txt

=========================================================================
Deprecation: #78279 - Deprecate top.TYPO3.Backend.ContentContainer.iframe
=========================================================================

See :issue:`78279`

Description
===========

The property :js:`top.TYPO3.Backend.ContentContainer.iframe` has been marked as deprecated.


Impact
======

Using this property will stop working in TYPO3 v9.


Affected Installations
======================

All installations using :js:`top.TYPO3.Backend.ContentContainer.iframe`.


Migration
=========

Use accessor method :js:`top.TYPO3.Backend.ContentContainer.get()` instead.

.. index:: Backend, JavaScript
