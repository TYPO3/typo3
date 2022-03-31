.. include:: /Includes.rst.txt

===============================================================================
Breaking: #91909 - sys_collection database tables moved into external extension
===============================================================================

See :issue:`91909`

Description
===========

The generic :sql:`sys_collection` database table and its MM table :sql:`sys_collection_entries`, which
holds information of connected records to a "Record Collection" have
been removed from TYPO3 Core.

This feature was added as a more generic approach of the `sys_file_collection`
definition along with the File Abstraction Layer in TYPO3 v6.0. The file collection
allows to create a group of files (e.g. from a folder, or from a category).

However, the more generic API was never picked up in TYPO3 Core since 2012.

The database table, the TCA definition (for editing the records
in the database), and the PHP API are now available in a separate
extension installable via the TYPO3 Extension Repository (https://extensions.typo3.org)
or via composer ("friendsoftypo3/legacy-collections").

The third-party extension can be used as a 1:1 drop-in replacement
for the removed Core functionality.


Impact
======

It is not possible to modify / edit :sql:`sys_collection` records anymore in the TYPO3 Backend.

The database tables are not defined anymore, neither is the TCA definition.

Accessing the PHP API class will result in fatal PHP errors.


Affected Installations
======================

Any TYPO3 installation using the database tables belonging to the :sql:`sys_collection` feature
which is very unlikely.


Migration
=========

Use the upgrade wizard or install the `legacy_collections` extension
to re-add the functionality - but only if it is needed.

As the PHP classes have a class alias, everything should work
as before.

.. index:: Database, TCA, FullyScanned, ext:core
