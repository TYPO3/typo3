
.. include:: /Includes.rst.txt

==========================================
Breaking: #72368 - TYPO3 Constants removed
==========================================

See :issue:`72368`

Description
===========

The PHP constants `TYPO3_enterInstallScript` and `TYPO3_cliMode` and the global variable `$GLOBALS['TYPO3_AJAX']` which were used when a TYPO3
Request was initialized have been removed. They have been replaced by an alternative to use the `TYPO3_REQUESTTYPE` constant at the very beginning of each
TYPO3 request.


Impact
======

Checking for the mentioned constants and global variable have no effect anymore and may lead to unexpected behaviour.

If not checked if the constant even was defined, the application will stop immediately.


Affected Installations
======================

Any installation which uses a third-party extension using these constants.


Migration
=========

Use `TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI` or `TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL` instead.

.. index:: PHP-API
