.. include:: /Includes.rst.txt

.. _deprecation-97231:

==============================================================
Deprecation: #97231 - Unused Interface for inline element hook
==============================================================

See :issue:`97231`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook']`
required hook implementations to implement :php:`\TYPO3\CMS\Backend\Form\Element\InlineElementHookInterface`.
Since the mentioned hook has been :doc:`removed <../12.0/Breaking-97231-RemovedHookForManipulatingInlineElementControls>`,
the interface is not in use anymore and has been marked as deprecated.

Impact
======

Using the interface has no effect anymore and the extension scanner will
report any usage.

Affected Installations
======================

TYPO3 installations using the PHP interface in custom extension code.

Migration
=========

The PHP interface is still available for TYPO3 v12.x, so extensions can
provide a version which is compatible with TYPO3 v11 (using the hook)
and TYPO3 v12.x (using the new :doc:`PSR-14 events <../12.0/Feature-97231-PSR-14EventsForModifyingInlineElementControls>`),
at the same time.
Remove any usage of the PHP interface and use the new PSR-14
events to avoid any further problems in TYPO3 v13+.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
