
.. include:: /Includes.rst.txt

================================================================
Feature: #19494 - Add SELECTmmQuery method to DatabaseConnection
================================================================

See :issue:`19494`

Description
===========

A new method `SELECT_mm_query` has been added to the `DatabaseConnection` class.
This method has been extracted from `exec_SELECT_mm_query` to separate the building
and execution of M:M queries.

This enables the use of the query building in the database abstraction layer.

Example:

.. code-block:: php

  $query = SELECT_mm_query('*', 'table1', 'table1_table2_mm', 'table2', 'AND table1.uid = 1', '', 'table1.title DESC');


.. index:: PHP-API, Database
