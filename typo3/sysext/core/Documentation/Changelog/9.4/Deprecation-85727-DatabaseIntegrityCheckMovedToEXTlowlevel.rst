.. include:: /Includes.rst.txt

==================================================================
Deprecation: #85727 - DatabaseIntegrityCheck moved to EXT:lowlevel
==================================================================

See :issue:`85727`

Description
===========

The PHP class :php:`TYPO3\CMS\Core\Integrity\DatabaseIntegrityCheck` has been moved from the system
extension `core` to `lowlevel`. The PHP class has been renamed to
:php:`TYPO3\CMS\Lowlevel\Integrity\DatabaseIntegrityCheck`.


Impact
======

Calling the old class name will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation where this PHP class is in use within a TYPO3 extension.


Migration
=========

Ensure that the system extension `lowlevel` is installed, and the caller code uses the new class name.

For TYPO3 v9, the old class is kept in place and will be removed in TYPO3 v10.

.. index:: ext:lowlevel, FullyScanned
