.. include:: /Includes.rst.txt

.. _breaking-102624-1701943942:

======================================================================
Breaking: #102624 - PSR-14 Event for modifying image source collection
======================================================================

See :issue:`102624`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getImageSourceCollection']`
has been removed in favor of the new PSR-14 event
:php:`\TYPO3\CMS\Frontend\ContentObject\Event\ModifyImageSourceCollectionEvent`.


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
Use the :doc:`PSR-14 event <../13.0/Feature-102624-PSR-14EventForModifyingImageSourceCollection>`
to allow greater influence in the functionality.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
