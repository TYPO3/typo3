.. include:: /Includes.rst.txt

.. _breaking-97231:

========================================================================
Breaking: #97231 - Removed hook for manipulating inline element controls
========================================================================

See :issue:`97231`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook']`
has been removed in favor of the new PSR-14 events :php:`\TYPO3\CMS\Backend\Form\Event\ModifyInlineElementEnabledControlsEvent`
and :php:`\TYPO3\CMS\Backend\Form\Event\ModifyInlineElementControlsEvent`.

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

Use the :doc:`PSR-14 events <../12.0/Feature-97231-PSR-14EventsForModifyingInlineElementControls>`
to allow greater influence in the functionality.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
