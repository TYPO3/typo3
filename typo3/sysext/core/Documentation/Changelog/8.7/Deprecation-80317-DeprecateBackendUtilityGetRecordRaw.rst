.. include:: ../../Includes.txt

==============================================================
Deprecation: #80317 - Deprecate BackendUtility::getRecordRaw()
==============================================================

See :issue:`80317`

Description
===========

Method :php:`BackendUtility::getRecordRaw()` has been deprecated and should not be
used any longer.


Impact
======

Extensions using above methods will throw a deprecation warning.


Affected Installations
======================

All installations and extensions using the method :php:`BackendUtility::getRecordRaw()`.


Migration
=========

Use the QueryBuilder instead and remove all restrictions.
For further information follow this link: querybuilder_

.. _querybuilder: https://docs.typo3.org/typo3cms/CoreApiReference/ApiOverview/Database/QueryBuilder/Index.html

.. index:: Backend, Database, PHP-API
