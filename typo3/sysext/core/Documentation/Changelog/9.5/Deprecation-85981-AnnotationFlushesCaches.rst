
.. include:: ../../Includes.txt

===============================================
Deprecation: #83094 - Annotation @flushesCaches
===============================================

See :issue:`85981`

Description
===========

The :php:`@flushesCaches` annotation has been marked as deprecated and will be removed with TYPO3 v10.
The annotation was introduced during backport from FLOW and never implemented to actually do anything
useful. It will be removed without substitution.

With it, the method :php:`TYPO3\CMS\Extbase\Mvc\Cli\Command->isFlushingCaches()` has been marked as deprecated
and will also be removed in TYPO3 v10.


Impact
======

Usage of Annotation :php:`@flushesCaches` and method :php:`TYPO3\CMS\Extbase\Mvc\Cli\Command->isFlushingCaches()`
will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All extensions that use :php:`@flushesCaches` or call the method :php:`TYPO3\CMS\Extbase\Mvc\Cli\Command->isFlushingCaches()`.


Migration
=========

Just remove annotation and method call. They did not do anything before.

.. index:: PHP-API, ext:extbase, FullyScanned
