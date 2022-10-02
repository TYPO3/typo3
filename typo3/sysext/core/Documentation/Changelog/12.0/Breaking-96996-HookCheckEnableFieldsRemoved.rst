.. include:: /Includes.rst.txt

.. _breaking-96996:

===================================================
Breaking: #96996 - Hook "checkEnableFields" removed
===================================================

See :issue:`96996`

Description
===========

The previous TYPO3 Hook "hook_checkEnableFields" registered via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_checkEnableFields']`
has been removed in favor of a new PSR-14 event
:php:`TYPO3\CMS\Core\Domain\Access\RecordAccessGrantedEvent`.

Impact
======

Hooks in third-party extensions will not be executed anymore.

Affected Installations
======================

TYPO3 installations with custom extensions using this hook. The
extension scanner will notify about usages.

Migration
=========

Register a new PSR-14 event listener for :ref:`RecordAccessGrantedEvent <feature-96996-1663513388>`
in the extension's :file:`Services.yaml` to keep TYPO3 v12+ compatibility.

Extensions can then provide compatibility with TYPO3 v11 and TYPO3 v12 at
the same time.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
