.. include:: /Includes.rst.txt

.. _breaking-98490-1664580829:

==========================================================================
Breaking: #98490 - Various hooks and methods changed in DatabaseRecordList
==========================================================================

See :issue:`98490`

Description
===========

The following hooks have been removed

*   :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable']`
*   :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['modifyQuery']`
*   :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['makeSearchStringConstraints']`

They were mainly used within the list module / record listing or the Element Browser
to modify the database query altering the result set of records rendered.

Along with the hooks, various method signatures within :php:`DatabaseRecordList`
have been changed.

*   :php:`DatabaseRecordList->getQueryBuilder()` has the arguments `$pageId`
    and `$additionalConstraints` removed
*   :php:`DatabaseRecordList->getTable()` only uses one argument now
*   :php:`DatabaseRecordList->makeSearchString()` is now marked as protected

Impact
======

Extensions adding implementations for these hooks have no effect anymore.

Extensions making use of calling the PHP methods directly will result in
a fatal PHP error.

Affected installations
======================

TYPO3 installations with third-party extensions modifying the record list via
the hooks above.

Migration
=========

Use the new PSR-14 event :php:`ModifyDatabaseQueryForRecordListingEvent`
which serves at the very end to alter the actual QueryBuilder object to modify
the database query before it is executed.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
