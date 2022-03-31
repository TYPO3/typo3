
.. include:: /Includes.rst.txt

===============================================
Deprecation: #73185 - Deprecate NullTimeTracker
===============================================

See :issue:`73185`

Description
===========

The class `\TYPO3\CMS\Core\TimeTracker\NullTimeTracker` has been marked as deprecated in favor of the TimeTracker class.


Impact
======

Calling this class directly will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 instance using `\TYPO3\CMS\Core\TimeTracker\NullTimeTracker` directly within an extension or third-party code.


Migration
=========

Initialize `\TYPO3\CMS\Core\TimeTracker\TimeTracker` with false as first parameter.

.. index:: PHP-API
