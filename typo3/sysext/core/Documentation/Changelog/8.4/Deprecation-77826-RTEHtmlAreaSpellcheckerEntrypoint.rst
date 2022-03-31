.. include:: /Includes.rst.txt

=========================================================
Deprecation: #77826 - RTEHtmlArea Spellchecker entrypoint
=========================================================

See :issue:`77826`

Description
===========

The entry point for HTTP Requests `SpellCheckingController->main` within RTEHtmlArea has been marked as deprecated.


Impact
======

Calling the PHP method above will trigger a deprecation log entry.


Affected Installations
======================

All TYPO3 instances calling this PHP method.


Migration
=========

Use `SpellCheckingController->processRequest` instead.

.. index:: PHP-API, RTE, Backend
