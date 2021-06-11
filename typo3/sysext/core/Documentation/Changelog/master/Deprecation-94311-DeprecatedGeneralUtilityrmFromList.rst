.. include:: ../../Includes.txt

===========================================================
Deprecation: #94311 - Deprecated GeneralUtility::rmFromList
===========================================================

See :issue:`94311`

Description
===========

The method :php:`GeneralUtility::rmFromList()` is unused within TYPO3
Core since v10. Because of this and the fact that this method would
anyways better belong to :php:`StringUtility` it has now been deprecated
in :php:`GeneralUtility` and will be removed in TYPO3 v12.

Impact
======

Calling the method will log a deprecation warning and the method will
be dropped with TYPO3 v12.

Affected Installations
======================

All TYPO3 installations calling this method in custom code. The extension
scanner will find all usages as strong match.

Migration
=========

Replace all usages of the method in your extension code.

.. index:: PHP-API, FullyScanned, ext:core
