
.. include:: /Includes.rst.txt

========================================================
Breaking: #72381 - Removed deprecated code from EXT:dbal
========================================================

See :issue:`72381`

Description
===========

The following methods of `\TYPO3\CMS\Dbal\Database\DatabaseConnection` have been removed:

* `MySQLActualType`
* `MySQLMetaType`
* `MetaType`


Impact
======

Using the methods above directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use custom calls to DatabaseConnection class via the methods above.


Migration
=========

`MySQLActualType` call `dbmsSpecifics->getNativeFieldType` instead
`MySQLMetaType` call `dbmsSpecifics->getMetaFieldType` instead
`MetaType` call `getMetadata` instead

.. index:: PHP-API, ext:dbal
