.. include:: ../../Includes.txt

=====================================
Breaking: #82334 - AbstractRecordList
=====================================

See :issue:`82334`

Description
===========

The PHP classes :php:`AbstractRecordList` and :php:`AbstractDatabaseRecordList` have been marked as deprecated.

Some classes changed inheritances, which can be breaking for instance in hooks if they type hint or otherwise
check instance types of these classes:

* :php:`PageLayoutView` no longer extends :php:`AbstractDatabaseRecordList`
* :php:`FileList` no longer extends :php:`AbstractRecordList`
* :php:`DatabaseRecordList` no longer extends :php:`AbstractDatabaseRecordList`


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
