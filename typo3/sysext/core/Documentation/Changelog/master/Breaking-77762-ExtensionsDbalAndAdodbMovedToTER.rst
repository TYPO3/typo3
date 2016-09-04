
.. include:: ../../Includes.txt

=========================================================
Breaking: #77762 - Extensions dbal and adodb moved to TER
=========================================================

See :issue:`77762`

Description
===========

The legacy extensions `dbal` and `adodb` have been removed from the TYPO3 CMS core and are only available as TER extension.


Impact
======

Tables located on non-MySQL databases stop working until `adodb` and `dbal` are installed from TER if a third party extensions uses the old `TYPO3_DB` API to query those tables.


Affected Installations
======================

Most installations are not affected. Instances are only affected if a loaded extension uses the old `TYPO3_DB` database API, if `dbal` and `adodb` have been loaded and if an active table mapping to non-MySQL databases is configured.


Migration
=========

Use the upgrade wizard provided by the install tool to fetch and load the extensions from TER.
