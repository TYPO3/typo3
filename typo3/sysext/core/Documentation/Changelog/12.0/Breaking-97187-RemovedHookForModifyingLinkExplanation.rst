.. include:: /Includes.rst.txt

.. _breaking-97187:

==============================================================
Breaking: #97187 - Removed hook for modifying link explanation
==============================================================

See :issue:`97187`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['linkHandler']`
has been removed in favor of a new PSR-14 event :php:`\TYPO3\CMS\Backend\Form\Event\ModifyLinkExplanationEvent`.

Impact
======

Any hook implementation registered is not executed anymore in
TYPO3 v12.0+. The extension scanner will report possible usages.

Affected Installations
======================

All TYPO3 installations using this hook in custom extension code.

Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v11 (using the hook) and v12+ (using the new event).

Use the :doc:`PSR-14 event <../12.0/Feature-97187-PSR-14EventForModifyingLinkExplanation>`
as an improved replacement.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
