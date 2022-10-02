.. include:: /Includes.rst.txt

.. _breaking-96983:

=====================================
Breaking: #96983 - TableColumnSubType
=====================================

See :issue:`96983`

Description
===========

The class :php:`TYPO3\CMS\Core\Type\Enumeration\TableColumnSubType` has been
removed. It has no use anymore, since TCA option `internal_type` is not
evaluated. It was set for the Extbase class :php:`ColumnMap`, but even there it
had no direct usage.

Impact
======

In the rare case, that the class :php:`TableColumnSubType` is used in
custom code, it will result in a PHP fatal error.

Affected Installations
======================

All installations that use :php:`TableColumnSubType` directly in their custom
code.

Migration
=========

There is no migration, since this enumeration has no use.

.. index:: PHP-API, FullyScanned, ext:core
