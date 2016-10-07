
.. include:: ../../Includes.txt

==============================================
Breaking: #72602 - Removed unzip functionality
==============================================

See :issue:`72602`

Description
===========

The legacy functionality to unzip files from outside the document root has been removed.

Additionally, the corresponding option `$TYPO3_CONF_VARS[BE][unzip_path]` and
the class member `ExtendedFileUtility::$unzipPath` have been removed as well.

Legacy methods from the Extbase domain model BackendUser named `isFileUnzipAllowed`
and `setFileUnzipAllowed` have been removed.


Impact
======

Calling the entry point `FileController` using unzip action will have no effect anymore.

Using the Extbase domain model methods will result in a fatal PHP error.


Migration
=========

Use a third-party extension to integrate unzip functionality into TYPO3.

.. index:: PHP-API, LocalConfiguration, Backend
