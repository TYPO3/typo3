.. include:: /Includes.rst.txt

.. _breaking-102581-1701449553:

=======================================================================
Breaking: #102581 - Removed hook for manipulating ContentObjectRenderer
=======================================================================

See :issue:`102581`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['postInit']`
has been removed in favor of the new PSR-14 event
:php:`\TYPO3\CMS\Frontend\ContentObject\Event\AfterContentObjectRendererInitializedEvent`.


Impact
======

Any hook implementation registered is not executed anymore
in TYPO3 v13+.


Affected installations
======================

TYPO3 installations with custom extensions using this hook.


Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v12 (using the hook) and v13+ (using the new event)
when implementing the event as well without any further deprecations.
Use the :doc:`PSR-14 event <../13.0/Feature-102581-PSR-14EventForModifyingContentObjectRenderer>`
to allow greater influence in the functionality.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
