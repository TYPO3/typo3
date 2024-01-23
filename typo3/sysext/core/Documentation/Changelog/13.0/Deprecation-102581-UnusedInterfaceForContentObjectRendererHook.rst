.. include:: /Includes.rst.txt

.. _deprecation-102581-1701449501:

======================================================================
Deprecation: #102581 - Unused Interface for ContentObjectRenderer hook
======================================================================

See :issue:`102581`

Description
===========

To use the :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['postInit']`
hook, implementations had to implement :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectPostInitHookInterface`.
Since the mentioned hook has been :doc:`removed <../13.0/Breaking-102581-RemovedHookForManipulatingContentObjectRenderer>`,
the interface is not in use anymore and has been marked as deprecated.


Impact
======

Using the interface has no effect anymore and the extension scanner will
report any usage.


Affected installations
======================

TYPO3 installations using the PHP interface in custom extension code.


Migration
=========

The PHP interface is still available for TYPO3 v13, so extensions can
provide a version which is compatible with TYPO3 v12 (using the hook)
and TYPO3 v13 (using the new :doc:`PSR-14 event <../13.0/Feature-102581-PSR-14EventForModifyingContentObjectRenderer>`),
at the same time. Remove any usage of the PHP interface and use the new PSR-14
event to avoid any further problems in TYPO3 v14+.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
