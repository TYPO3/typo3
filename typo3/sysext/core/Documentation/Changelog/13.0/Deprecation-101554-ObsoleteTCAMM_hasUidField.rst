.. include:: /Includes.rst.txt

.. _deprecation-101554-1691480627:

==================================================
Deprecation: #101554 - Obsolete TCA MM_hasUidField
==================================================

See :issue:`101554`

Description
===========

When configuring :php:`MM` relations in TCA, the field :php:`MM_hasUidField`
has been obsoleted: A :sql:`uid` column is only needed when :php:`multiple`
is set to true - when a record is allowed to be selected multiple times in
a relation. In this case, the :sql:`uid` field is added automatically by the
database analyzer.


Impact
======

The TCA configuration option :php:`MM_hasUidField` is obsolete and can be removed.

The TCA migration, which is performed during TCA warmup, will automatically
remove this option and creates according log entries, if needed.


Affected installations
======================

Instances with extensions using :php:`MM` relations may be affected.


Migration
=========

Remove all occurrences of php:`MM_hasUidField` from TCA. The :sql:`uid` column
is added as primary key automatically, if :php:`multiple = true` is set, otherwise
a combined primary key of the fields :sql:`uid_local`, :sql:`uid_foreign` plus
eventually :sql:`tablenames` and :sql:`fieldname` is used.


.. index:: TCA, NotScanned, ext:core
