.. include:: /Includes.rst.txt

.. _breaking-102855-1705567984:

==========================================================================
Breaking: #102855 - Removed LinkService resolveByStringRepresentation hook
==========================================================================

See :issue:`102855`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Link']['resolveByStringRepresentation']`
has been removed in favor of the new PSR-14 event
:php:`\TYPO3\CMS\Core\LinkHandling\Event\AfterLinkResolvedByStringRepresentationEvent`
event.

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
Use the :doc:`PSR-14 event <../13.0/Feature-102855-PSR-14EventForModifyingResolvedLinkResultData>`
to allow greater influence in the functionality.

.. index:: PHP-API, FullyScanned, ext:core
