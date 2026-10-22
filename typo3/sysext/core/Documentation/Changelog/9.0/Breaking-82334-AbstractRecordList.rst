.. include:: /Includes.rst.txt

.. _breaking-82334:

=====================================
Breaking: #82334 - AbstractRecordList
=====================================

See :issue:`82334`

Description
===========

The PHP classes :php-short:`\TYPO3\CMS\Backend\RecordList\AbstractRecordList` and :php-short:`\TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList` have been marked as deprecated.

Some classes changed inheritances, which can be breaking for instance in hooks if they type hint or otherwise
check instance types of these classes:

* :php-short:`\TYPO3\CMS\Backend\View\PageLayoutView` no longer extends :php-short:`\TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList`
* :php-short:`\TYPO3\CMS\Filelist\FileList` no longer extends :php-short:`\TYPO3\CMS\Backend\RecordList\AbstractRecordList`
* :php-short:`\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList` no longer extends :php-short:`\TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList`


Impact
======

Calling the constructor in these classes triggers a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation with an extension accessing or extending the deprecated classes.


Migration
=========

The extension scanner checks if the classes are used.

All extension authors are encouraged to copy the content of these Classes into their child classes.

.. index:: Backend, PHP-API, FullyScanned
