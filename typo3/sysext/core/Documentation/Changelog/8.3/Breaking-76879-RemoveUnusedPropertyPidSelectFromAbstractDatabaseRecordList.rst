
.. include:: /Includes.rst.txt

===================================================================================
Breaking: #76879 - Remove unused property pidSelect from AbstractDatabaseRecordList
===================================================================================

See :issue:`76879`

Description
===========

The unused public property :php:`pidSelect` has been removed from the :php:`AbstractDatabaseRecordList` class.


Impact
======

Extensions which use the public property will throw a fatal error.


Affected Installations
======================

All installations with a 3rd party extension using the :php:`pidSelect` property.


Migration
=========

Use :php:`AbstractDatabaseRecordList::setOverridePageIdList()` to set an array of page ids
that should be used to restrict the query.

.. index:: PHP-API, Backend
