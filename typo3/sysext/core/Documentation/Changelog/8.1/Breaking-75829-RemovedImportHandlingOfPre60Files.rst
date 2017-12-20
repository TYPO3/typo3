
.. include:: ../../Includes.txt

===================================================================
Breaking: #75829 - Removed handling of pre 6.0 files when importing
===================================================================

See :issue:`75829`

Description
===========

The functionality for importing .t3d files created from installations lower than TYPO3 CMS 6.0 has been removed.

The following public method has been removed: :php`\TYPO3\CMS\Impexp\Import::fixCharsets()`.


Impact
======

Importing files into TYPO3 v8 that were created from a TYPO3 4.x installations will result in unexpected behavior,
especially when dealing with files and relations.

Calling the PHP method above will result in a fatal PHP error.


Affected Installations
======================

Any installation using the t3d import functionality for importing files that were created from a TYPO3 instance older
than TYPO3 6.0.0.


Migration
=========

It is recommended to import files in a 6.x or 7.x installation and export the files from there again to import them
in TYPO3 v8.

.. index:: PHP-API, Backend, ext:impexp