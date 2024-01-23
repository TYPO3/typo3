.. include:: /Includes.rst.txt

.. _deprecation-102745-1705054271:

========================================================
Deprecation: #102745 - Unused interface for stdWrap hook
========================================================

See :issue:`102745`


Description
===========

The ContentObject stdWrap hook required hook implementations to implement the
:php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectStdWrapHookInterface`.
Since the mentioned hook has been :doc:`removed <../13.0/Breaking-102745-RemovedContentObjectStdWrapHook>`,
the interface is not in use anymore and has been marked as deprecated.

Impact
======

Using the interface has no effect anymore and the extension scanner will
report any usage.

Affected installations
======================

TYPO3 installations using the PHP interface in custom extension code.

Migration
=========

The PHP interface is still available for TYPO3 v13, so extensions can
provide a version which is compatible with TYPO3 v12 (using the hook)
and TYPO3 v13 (using the new :doc:`PSR-14 events <../13.0/Feature-102745-PSR-14EventsForModifyingContentObjectStdWrapFunctionality>`),
at the same time. Remove any usage of the PHP interface and use the new PSR-14
events to avoid any further problems in TYPO3 v14+.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
