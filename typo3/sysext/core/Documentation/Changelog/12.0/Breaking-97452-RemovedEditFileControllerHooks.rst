.. include:: /Includes.rst.txt

.. _breaking-97452:

===================================================
Breaking: #97452 - Removed EditFileController hooks
===================================================

See :issue:`97452`

Description
===========

The hooks :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['preOutputProcessingHook']`
and :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['postOutputProcessingHook']`
have been removed, since adjusting the generated content can be achieved using template overrides
and modifing the form data, used to generate the edit file form, can be done
using the PSR-14 :php:`TYPO3\CMS\Filelist\Event\ModifyEditFileFormDataEvent`.

Impact
======

Any hook implementation registered is not executed anymore in TYPO3 v12.0+.
The extension scanner will report possible usages.

Affected Installations
======================

All TYPO3 installations using these hook in custom extension code. This is
pretty unlikely, since both hooks were of limited use.

Migration
=========

The form data modification, allowed by :php:`preOutputProcessingHook`, can be
achieved with the new :ref:`PSR-14 ModifyEditFileFormDataEvent <feature-98521-1664890745>`.

The content manipulation :php:`postOutputProcessingHook` hook can be substituted with a template override
as outlined in :ref:`this changelog entry <feature-96812>`.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
