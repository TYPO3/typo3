
.. include:: ../../Includes.txt

===============================================================================
Feature: #67290 - DBAL: DBMS specific conversion between Meta/MySQL field types
===============================================================================

See :issue:`67290`

Description
===========

DBAL did a generic translation between MySQL native and DBMS specific field types.

The translation of field types has been enhanced to allow more specific conversions per DBMS driver.
Overrides for PostgreSQL have been added with optimized mappings for BLOB, SERIAL, DOUBLE and INTEGER columns.


Impact
======

Running PostgreSQL, the Upgrade Wizard in the Install Tool will show a lot of field alterations as the optimized mappings will be used.
