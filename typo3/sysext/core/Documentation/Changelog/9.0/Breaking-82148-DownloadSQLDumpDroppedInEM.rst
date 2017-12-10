.. include:: ../../Includes.txt

==================================================
Breaking: #82148 - Download SQL dump dropped in EM
==================================================

See :issue:`82148`

Description
===========

The "Download SQL Dump" feature in extension manager list view has been dropped,
it is no longer possible to download the dump file this button created.


Impact
======

The button in the extension manager is gone along with various classes
and methods that took care of the functionality:

* Dropped class :php:`TYPO3\CMS\Extensionmanager\Utility\DatabaseUtility`
* Dropped class :php:`TYPO3\CMS\Extensionmanager\ViewHelpers\DownloadExtensionDataViewHelper`
* Dropped class :php:`TYPO3\CMS\Install\Service\SqlExpectedSchemaService`
* Dropped class :php:`TYPO3\CMS\Install\Service\SqlSchemaMigrationService`
* Dropped method :php:`TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility->sendSqlDumpFileToBrowserAndDelete()`


Migration
=========

The core provides the "Import / Export" extension to manage database data,
furthermore the "List" module has an CSV export feature.

For true database backups we recommend the CLI tools of the database engine
or dedicated GUI applications for this task.

.. index:: Backend, Database, PHP-API, FullyScanned