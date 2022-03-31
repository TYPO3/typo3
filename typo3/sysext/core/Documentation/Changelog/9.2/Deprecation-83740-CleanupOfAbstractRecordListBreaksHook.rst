.. include:: /Includes.rst.txt

===============================================================
Deprecation: #83740 - Cleanup of AbstractRecordList breaks hook
===============================================================

See :issue:`83740`

Description
===========

The hook `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['buildQueryParameters']`
has been marked as deprecated. It was a hook to modify the current database query but used in multiple classes which
leads to some issues. For this reason, the old hook is now marked as deprecated and will be removed in v10.


Impact
======

Registering a hook in `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['buildQueryParameters']`
will trigger a deprecation warning.


Affected installations
======================

Instances with extensions using the hook `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['buildQueryParameters']`


Migration
=========

Two new hooks are available to achieve the same things.

Please see:

`Feature-83740-CleanupOfAbstractRecordListBreaksHook.rst <https://docs.typo3.org/typo3cms/extensions/core/Changelog/9.2/Feature-83740-CleanupOfAbstractRecordListBreaksHook.html>`_

.. index:: Backend, Database, PHP-API, FullyScanned
