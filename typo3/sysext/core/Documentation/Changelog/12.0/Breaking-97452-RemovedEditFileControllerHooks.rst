.. include:: /Includes.rst.txt

.. _breaking-97452:

===================================================
Breaking: #97452 - Removed EditFileController hooks
===================================================

See :issue:`97452`

Description
===========

The hooks :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['preOutputProcessingHook']` and
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['postOutputProcessingHook']` have been removed.
The same behavior may be achieved using template overrides.

Impact
======

Any hook implementation registered is not executed anymore in TYPO3 v12.0+.
The extension scanner will report possible usages.

Affected Installations
======================

All TYPO3 installations using these hook in custom extension code. This is pretty
unlikely, since both hooks were of limited use.

Migration
=========

The content preparation allowed by :php:`preOutputProcessingHook` can be achieved with
:ref:`FormEngine data providers <t3coreapi:FormEngine-DataCompiling>`.

The content manipulation :php:`postOutputProcessingHook` hook can be substituted with a template override
as outlined in :doc:`this changelog entry <Feature-96812-OverrideBackendTemplatesWithTSconfig>`.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
