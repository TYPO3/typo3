.. include:: /Includes.rst.txt

.. _feature-97173-1663950087:

=================================================================
Feature: #97173 - Auto creation of database fields for TCA "slug"
=================================================================

See :issue:`97173`

Description
===========

TYPO3 automatically creates database fields for TCA type :php:`slug` columns,
if they have not already been defined in an extension's :file:`ext_tables.sql`
file.

Impact
======

The corresponding database field definition of a slug field can be omitted from
:file:`ext_tables.sql`.

.. index:: Database, ext:core
