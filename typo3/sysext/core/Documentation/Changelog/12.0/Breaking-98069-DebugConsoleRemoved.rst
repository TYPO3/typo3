.. include:: /Includes.rst.txt

.. _breaking-98069-1659536025:

=======================================
Breaking: #98069 - DebugConsole removed
=======================================

See :issue:`98069`

Description
===========

The DebugConsole comes from ExtJS times and was triggered when e.g. a request
failed to give a developer its response, including the stacktrace. Nowadays,
browsers offer a console allowing to investigate requests. Also, PHP debuggers
(e.g. xdebug) are commonly known and used, which makes the DebugConsole obsolete.

Impact
======

Triggering the DebugConsole is not possible anymore. Also, the PHP method
:php:`\TYPO3\CMS\Core\Utility\DebugUtility::debug()` always renders the plain
debug output to the client.

The 3rd argument :php:`$group` of the method
:php:`\TYPO3\CMS\Core\Utility\DebugUtility::debug()` is removed.

Affected installations
======================

All installations are affected.

Migration
=========

No migration is available.

.. index:: Backend, JavaScript, PHP-API, PartiallyScanned, ext:backend
