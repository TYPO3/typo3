.. include:: /Includes.rst.txt

===========================================================
Deprecation: #89756 - BackendUtility::TYPO3_copyRightNotice
===========================================================

See :issue:`89756`

Description
===========

The PHP method :php:`TYPO3\CMS\Backend\Utility\BackendUtility::TYPO3_copyRightNotice` that is
used to display information about the warranty and copyright of the product, e.g. used in the
login screen, has been superseded by a new API :php:`TYPO3\CMS\Core\Information\Typo3Information`.

The existing static method has been marked as deprecated.


Impact
======

Calling the method will trigger a deprecation warning, but work as
before until TYPO3 v11.0.


Affected Installations
======================

TYPO3 installations with custom extensions explicitly calling this
method. Run the Extension Scanner in the "Upgrade" module to see
if you are affected.


Migration
=========

Use the new :php:`Typo3Information` PHP class, and its method :php:`getCopyrightNotice()` which will return the same output.

.. index:: PHP-API, FullyScanned, ext:backend
