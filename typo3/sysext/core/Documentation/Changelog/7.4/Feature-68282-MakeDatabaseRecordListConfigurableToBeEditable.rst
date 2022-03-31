
.. include:: /Includes.rst.txt

=====================================================================
Feature: #68282 - Make DatabaseRecordList configurable to be editable
=====================================================================

See :issue:`68282`

Description
===========

A new property `editable` is added to `\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList` which is set to TRUE
by default. If set to FALSE, the records can't be edited.


Impact
======

The record list in the Element Browser benefits from the new setting as the localization view is now enabled. This will
show editors translated records properly intended below the record with the default language.


.. index:: PHP-API, Backend
