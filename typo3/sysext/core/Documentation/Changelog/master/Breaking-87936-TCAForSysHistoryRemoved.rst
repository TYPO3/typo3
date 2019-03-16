.. include:: ../../Includes.txt

==============================================
Breaking: #87936 - TCA for sys_history removed
==============================================

See :issue:`87936`

Description
===========

The TCA definition for `sys_history` database table was removed. It was never shown in TYPO3 Backend,
and only in use for the BElog module as Extbase Domain Model. However, this relationship between
logs and sys_history was decoupled in TYP3 v9.0.

The database field "pid" which was "0" at all times, is now removed.


Impact
======

Accessing :php:`$GLOBALS[TCA][sys_history]` will trigger a PHP warning, and the contents of the array
are not available anymore.


Affected Installations
======================

Any TYPO3 installation with extensions accessing the global array by making use of
`sys_history`.


Migration
=========

If still needed, an extension should deliver the full TCA definition of `sys_history`.

.. index:: Database, TCA, FullyScanned, ext:core