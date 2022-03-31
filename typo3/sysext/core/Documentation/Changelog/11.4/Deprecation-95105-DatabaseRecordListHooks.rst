.. include:: /Includes.rst.txt

==============================================
Deprecation: #95105 - DatabaseRecordList hooks
==============================================

See :issue:`95105`

Description
===========

The TYPO3 hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions']`
which is used in the :php:`DatabaseRecordList` class for modifying the
behavior of each table listing, has been marked as deprecated.

Using this hook always required to implement the :php:`\TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface`,
which then required the corresponding hook class to implement four different
hook methods, even if only one of them was needed.

Furthermore are those methods no longer sufficient since e.g. the "controls"
and "clip" sections were merged together already. Therefore, also the
accompanied PHP interface :php:`TYPO3\CMS\Recordlist\RecordList\RecordListHookInterface`
has been marked as deprecated.

Impact
======

If the hook is registered in a TYPO3 installation, a PHP :php:`E_USER_DEPRECATED` error is triggered. The extension scanner also detects any usage
of the deprecated interface as strong, and the definition of the
hook as weak match.

Affected Installations
======================

TYPO3 installations with custom extensions using this hook.

Migration
=========

Migrate to the corresponding RecordList PSR-14 events:

- `\TYPO3\CMS\Recordlist\Event\ModifyRecordListTableActionsEvent`
- `\TYPO3\CMS\Recordlist\Event\ModifyRecordListHeaderColumnsEvent`
- `\TYPO3\CMS\Recordlist\Event\ModifyRecordListRecordActionsEvent`

.. index:: PHP-API, FullyScanned, ext:core
