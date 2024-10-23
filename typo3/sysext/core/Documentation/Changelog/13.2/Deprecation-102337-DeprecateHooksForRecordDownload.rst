.. include:: /Includes.rst.txt

.. _deprecation-102337-1715591179:

==========================================================
Deprecation: #102337 - Deprecate hooks for record download
==========================================================

See :issue:`102337`

Description
===========

The previously used hooks
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['customizeCsvHeader']`
and
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['customizeCsvRow']`,
used to manipulate the download / export configuration of records, triggered
in the :guilabel:`Web > List` backend module, have been deprecated in favor of a
new PSR-14 event :php:`TYPO3\CMS\Backend\RecordList\Event\BeforeRecordDownloadIsExecutedEvent`.

Details for migration and functionality can be found in :ref:`feature-102337-1715591178`

Impact
======

When the hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['customizeCsvHeader']`
or
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['customizeCsvRow']`
is executed, this will trigger a PHP deprecation warning.

The extension scanner will find possible usages with a weak match.

Affected installations
======================

All installations using
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['customizeCsvHeader']`
or
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList']['customizeCsvRow']`
are affected.


Migration
=========

The new PSR-14 event :php:`TYPO3\CMS\Backend\RecordList\Event\BeforeRecordDownloadIsExecutedEvent`
can be used as a near drop-in replacement.

.. index:: PHP-API, FullyScanned, ext:core
