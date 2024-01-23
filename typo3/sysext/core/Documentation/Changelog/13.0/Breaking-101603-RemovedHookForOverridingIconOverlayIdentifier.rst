.. include:: /Includes.rst.txt

.. _breaking-101603-1691322822:

=======================================================================
Breaking: #101603 - Removed hook for overriding icon overlay identifier
=======================================================================

See :issue:`101603`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Core\Imaging\IconFactory']['overrideIconOverlay']`
has been removed in favor of a new PSR-14 event :php:`\TYPO3\CMS\Core\Imaging\Event\ModifyRecordOverlayIconIdentifierEvent`.

Impact
======

Any hook implementation registered is not executed anymore in TYPO3 v13.0+.

Affected Installations
======================

TYPO3 installations with custom extensions using this hook. The extension
scanner will report usages as strong match.

Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v12 (using the hook) and v13+ (using the new event)
when implementing the event as well without any further deprecations.

Replace any hook usage with the new
:doc:`PSR-14 event <../13.0/Feature-101603-PSR-14EventForModifyingRecordOverlayIconIdentifier>`.

.. index:: Backend, PHP-API, FullyScanned, ext:core
