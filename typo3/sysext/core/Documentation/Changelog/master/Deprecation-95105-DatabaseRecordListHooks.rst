.. include:: ../../Includes.txt

==============================================
Deprecation: #95105 - DatabaseRecordList hooks
==============================================

See :issue:`95105`

Description
===========

The TYPO3 Hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions']`
which is used in the :php:`DatabaseRecordList` class for modifying the
behaviour of each table listing, has been deprecated.

Using this hook always required to implement the :php:`RecordListHookInterface`,
which then required the corresponding hook class to implement four different
hook methods, even if only one of them was needed.

Furthermore are those methods no longer sufficient since e.g. the "controls"
and "clip" sections were merged together already. Therefore, also the
accompanied PHP Interface :php:`TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface`
has been marked as deprecated.

Impact
======

If the hook is registered in a TYPO3 installation, a PHP deprecation
message is triggered. The extension scanner also detects any usage
of the deprecated interface as strong, and the definition of the
hook as weak match.

Affected Installations
======================

TYPO3 installations with custom extensions using this hook.

Migration
=========

Migrate to the corresponding RecordList PSR-14 events:

- `ModifyRecordListTableActionsEvent`
- `ModifyRecordListHeaderColumnsEvent`
- `ModifyRecordListRecordActionsEvent`

.. index:: PHP-API, FullyScanned, ext:core
