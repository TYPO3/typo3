.. include:: /Includes.rst.txt

.. _deprecation-101793-1693356502:

================================================================
Deprecation: #101793 - DataHandler checkStoredRecords properties
================================================================

See :issue:`101793`

Description
===========

The backend :php:`DataHandler` had a functionality to verify written records
after they have been persisted in the database and log unexpected collisions.

This feature has been removed since it is rather useless with many databases
in strict mode nowadays and since the default configuration was to not
actually check single fields but to still create overhead by always
querying records from the database without benefit.

Two :php:`TYPO3_CONF_VARS` toggles have been obsoleted:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['checkStoredRecords']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['checkStoredRecordsLoose']`

Two :php:`DataHandler` properties have been marked as deprecated:

* :php:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkStoredRecords`
* :php:`\TYPO3\CMS\Core\DataHandling\DataHandler->checkStoredRecords_loose`

Impact
======

There should be little to no impact for instances, except some less database
queries when using the :php:`DataHandler`. Extensions setting the :php:`DataHandler`
properties should stop using them, they will be removed with TYPO3 v14 and
have no functionality with v13 anymore.


Affected installations
======================

In rare cases, instances with extensions setting the :php:`DataHandler` properties
are affected. The extension scanner will find possible usages with a weak
match.

Instances setting the :php:`TYPO3_CONF_VARS` toggles in :php:`settings.php`
are updated silently by the install tool during the upgrade process to TYPO3 v13.


Migration
=========

Extensions aiming for compatibility with TYPO3 v12 and v13 can continue to set the
properties :php:`DataHandler->checkStoredRecords` and :php:`DataHandler->checkStoredRecords_loose`,
they are kept in v13, but functionality bound to them is removed.

Extensions aiming for compatibility with TYPO3 v13 and above should remove
usages of :php:`DataHandler->checkStoredRecords` and :php:`DataHandler->checkStoredRecords_loose`,
they are without functionality in TYPO v13 and will be removed with TYPO3 v14.

.. index:: Database, LocalConfiguration, PHP-API, FullyScanned, ext:core
