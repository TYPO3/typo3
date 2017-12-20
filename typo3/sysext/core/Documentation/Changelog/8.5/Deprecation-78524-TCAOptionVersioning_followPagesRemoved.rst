.. include:: ../../Includes.txt

===============================================================
Deprecation: #78524 - TCA option versioning_followPages removed
===============================================================

See :issue:`78524`

Description
===========

The option `$TCA[$table][ctrl][versioning_followPages]` which was used for branch versioning has been removed.

Additionally the option `$TCA[$table][ctrl][versioningWS]` is now cast to boolean.

The branch / page versioning functionality was removed in TYPO3 v7, but the leftover functionality code has been
completely removed as well.


Impact
======

A deprecation message is thrown when scanning the TCA tree for these options not being properly set or removed.


Affected Installations
======================

Any TYPO3 installation with a TCA definition as mentioned above.


Migration
=========

Remove the setting `$TCA[$table][ctrl][versioning_followPages]` from any TCA definition.

If a TCA table has workspaces enabled, set the option `$TCA[$table][ctrl][versioningWS]` to a boolean (true/false) directly.

.. index:: TCA, ext:workspaces, Backend
