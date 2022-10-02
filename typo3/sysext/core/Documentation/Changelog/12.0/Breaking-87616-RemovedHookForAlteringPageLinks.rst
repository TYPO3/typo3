.. include:: /Includes.rst.txt

.. _breaking-87616:

=======================================================
Breaking: #87616 - Removed hook for altering page links
=======================================================

See :issue:`87616`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typolinkProcessing']['typolinkModifyParameterForPageLinks']`
has been removed in favor of a new PSR-14 event :php:`TYPO3\CMS\Frontend\Event\ModifyPageLinkConfigurationEvent`.

The event is called after TYPO3 has already prepared some functionality
within the :php:`PageLinkBuilder`. This therefore allows to modify more
properties, if needed.

Impact
======

Any hook implementation registered is not executed anymore
in TYPO3 v12.0+.

Affected Installations
======================

TYPO3 installations with custom extensions using this hook.

Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v11 (using the hook) and v12+ (using the new event).

Use the :doc:`PSR-14 event <../12.0/Feature-87616-PSR-14EventForModifyingPageLinkGeneration>`
to allow greater influence in the functionality.

.. index:: Frontend, FullyScanned, ext:frontend
