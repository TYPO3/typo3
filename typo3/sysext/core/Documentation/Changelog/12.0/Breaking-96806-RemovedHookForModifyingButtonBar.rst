.. include:: /Includes.rst.txt

.. _breaking-96806:

========================================================
Breaking: #96806 - Removed hook for modifying button bar
========================================================

See :issue:`96806`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook']`
has been removed in favor of a new PSR-14 event :php:`\TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent`.

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

Use the :doc:`PSR-14 event <../12.0/Feature-96806-PSR-14EventForModifyingButtonBar>`
as a direct replacement.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
