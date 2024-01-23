.. include:: /Includes.rst.txt

.. _breaking-99323-1704980682:

============================================================================
Breaking: #99323 - Removed hook for modifying records after fetching content
============================================================================

See :issue:`99323`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content_content.php']['modifyDBRow']`
has been removed in favor of the more powerful PSR-14
:php:`\TYPO3\CMS\Frontend\ContentObject\Event\ModifyRecordsAfterFetchingContentEvent`.


Impact
======

Any hook implementation registered is not executed anymore
in TYPO3 v13.0+.


Affected installations
======================

TYPO3 installations with custom extensions using this hook.


Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v12 (using the hook) and v13+ (using the new event)
when implementing the event as well without any further deprecations.
Use the :doc:`PSR-14 event <../13.0/Feature-99323-AddModifyRecordsAfterFetchingContentEvent>`
to allow greater influence in the functionality.

.. index:: PHP-API, FullyScanned, ext:frontend
