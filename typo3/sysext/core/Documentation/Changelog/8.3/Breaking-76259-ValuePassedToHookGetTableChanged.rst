
.. include:: ../../Includes.txt

========================================================
Breaking: #76259 - Value passed to hook getTable changed
========================================================

See :issue:`76259`

Description
===========

The value of :php:`$additionalWhere` passed to the method :php:`getDBlistQuery()`
as part of the hook `getTable` in :php:`\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList`
has changed and no longer includes the leading `AND`.


Impact
======

3rd Party extensions implementing the hook method need to ensure the leading `AND` is no
longer present. The leading `AND` should also not be returned anymore.


Affected Installations
======================


Installations using 3rd party extensions that implement the hook method.


Migration
=========

Migrate the hook method to no longer expect or prepend the leading `AND`.

.. index:: Database, PHP-API, Backend
