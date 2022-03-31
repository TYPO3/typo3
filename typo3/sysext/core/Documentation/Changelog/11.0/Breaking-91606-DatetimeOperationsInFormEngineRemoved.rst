.. include:: /Includes.rst.txt

=============================================================
Breaking: #91606 - Date/time operations in FormEngine removed
=============================================================

See :issue:`91606`

Description
===========

FormEngine supported to add or subtract a date or time range (depending on the
field type) by appending e.g. `+5` or `-42` to the field values. These kind of
operations have been removed as they are rather unknown and clumsy to use.


Impact
======

Using these operations doesn't have any effect anymore.


Affected Installations
======================

All installations of TYPO3 are affected.


Migration
=========

There is no migration possible.

.. index:: Backend, JavaScript, NotScanned, ext:backend
