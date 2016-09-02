=========================================================
Deprecation: #77826 - RTEHtmlArea Spellchecker entrypoint
=========================================================

Description
===========

The entrypoint for HTTP Requests `SpellCheckingController->main` within the RTEHtmlArea was marked as deprecated.


Impact
======

Calling the PHP method above will trigger a deprecation message.


Affected Installations
======================

All TYPO3 instances calling this PHP method.


Migration
=========

Use `SpellCheckingController->processRequest` instead.