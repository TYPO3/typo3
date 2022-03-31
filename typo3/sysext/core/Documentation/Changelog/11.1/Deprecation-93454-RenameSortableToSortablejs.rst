.. include:: /Includes.rst.txt

===================================================
Deprecation: #93454 - Rename Sortable to sortablejs
===================================================

See :issue:`93454`

Description
===========

Due to importing TypeScript declarations of SortableJS, it's required to make
the library available as :js:`sortablejs`. The previously used name :js:`Sortable` is
still available, but has been marked as deprecated.


Impact
======

There is no direct impact, as we cannot intercept loading the module to log a
deprecation message.


Affected Installations
======================

Every 3rd party extension using :js:`SortableJS` is affected.


Migration
=========

Change the import of the library to :js:`sortablejs`.

.. index:: JavaScript, NotScanned, ext:backend
