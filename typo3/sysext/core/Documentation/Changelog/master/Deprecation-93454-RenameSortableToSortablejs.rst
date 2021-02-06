.. include:: ../../Includes.txt

===================================================
Deprecation: #93454 - Rename Sortable to sortablejs
===================================================

See :issue:`93454`

Description
===========

Due to importing TypeScript declarations of SortableJS, it's required to make
the library available as `sortablejs`. The previously used name `Sortable` is
still available, but declared deprecated.


Impact
======

There is no direct impact, as we cannot intercept loading the module to log a
deprecation.


Affected Installations
======================

Every 3rd party extension using SortableJS is affected.


Migration
=========

Change the import of the library to `sortablejs`.

.. index:: JavaScript, NotScanned, ext:backend
