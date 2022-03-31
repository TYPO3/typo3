.. include:: /Includes.rst.txt

========================================
Breaking: #52877 - Remove ExtJS Viewport
========================================

See :issue:`52877`

Description
===========

The ExtJS component `TYPO3.Viewport` has been removed from the TYPO3 Core, `Ext.layout` and `Ext.Viewport` are no longer
used in the backend viewport.


Impact
======

- Calling the removed ExtJS components `TYPO3.Viewport` or `TYPO3.backendContentIframePanel` will result in an error
- The ability to stack content with cards with `TYPO3.Viewport.ContentCards` is no longer supported


Affected Installations
======================

Any TYPO3 installations using custom extensions based on ExtJS which rely on the above mentioned components.


Migration
=========

There is no migration available.

.. index:: Backend, JavaScript
