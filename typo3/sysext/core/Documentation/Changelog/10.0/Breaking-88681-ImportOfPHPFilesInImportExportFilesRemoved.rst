.. include:: /Includes.rst.txt

=====================================================================
Breaking: #88681 - Import of PHP files in Import/Export files removed
=====================================================================

See :issue:`88681`

Description
===========

Importing XML data via EXT:impexp previously allowed to import PHP files for Administrators
in TYPO3 Backend. This by-pass functionality is removed, and the configured File Deny Pattern
now applies for all imports in order to streamline import functionality with other file
operations within TYPO3 Core.


Impact
======

Importing XML files with embedded PHP files via EXT:impexp will trigger an import error and disallow
the import of the file.


Affected Installations
======================

Any TYPO3 installations using the data importer that use import files with included PHP files.


Migration
=========

Ensure to include PHP files into a custom local extension, as importing PHP code is highly
discouraged - even for administrators.

.. index:: PHP-API, NotScanned, ext:impexp
