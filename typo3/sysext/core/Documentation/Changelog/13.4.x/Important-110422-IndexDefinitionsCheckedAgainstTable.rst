.. include:: /Includes.rst.txt

.. _important-110422-1786292528:

====================================================================
Important: #110422 - Index definitions are checked against the table
====================================================================

See :issue:`110422`

Description
===========

A :file:`ext_tables.sql` table definition that puts an index on a column the
table does not declare is invalid. It is usually a typo, or a column that was
removed while the index over it stayed behind.

MySQL and PostgreSQL reject such an index themselves, so the update failed with
a database error there. SQLite accepts it: a quoted identifier that matches no
column counts as a string literal, so the index is created over a constant
expression and covers no column at all. A database in that state can no longer
be introspected, which stops the schema analysis of the install tool and of the
command line alike, and leaves no way to bring the database back into shape.

Index columns are therefore checked against the table before anything is
applied, and an index over a column the table does not have is reported as an
invalid definition:

.. code-block:: text

   [Semantic Error] Index "sorting" of table "tx_myext_item" is defined over
   column "sorting_value", which the table does not have.

The check runs on the merged definition of a table, not on a single statement,
so extensions can keep adding an index to a table of another extension in a
statement of their own that only carries the index.

Impact
======

An extension shipping such a definition made the schema update fail before, on
every platform except SQLite, where it damaged the database instead. It is now
reported as what it is, naming the table, the index and the column, and nothing
is applied until it is corrected.

.. index:: Database, ext_tables.sql, NotScanned, ext:core
