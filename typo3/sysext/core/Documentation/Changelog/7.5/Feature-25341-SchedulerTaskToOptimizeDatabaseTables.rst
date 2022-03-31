
.. include:: /Includes.rst.txt

============================================================
Feature: #25341 - Scheduler task to optimize database tables
============================================================

See :issue:`25341`

Description
===========

A scheduler task to run the `OPTIMIZE TABLE` command on selected
database tables has been added. The `OPTIMIZE TABLE` command
reorganizes the physical storage of table data and associated index
data to reduce storage space and improve I/O efficiency when
accessing the table. The exact changes made to each table depend
on the storage engine used by that table. For more information see
the `MySQL manual`_.

The scheduler task is meant for the MySQL database system and only
shows tables matching the MySQL storage engines MyISAM, InnoDB and
ARCHIVE. Using this task with DBAL and other DBMS is not supported
as the commands used are MySQL specific.


Impact
======

Optimizing tables is I/O intensive. On MySQL < 5.6.17 it also locks
the tables for the whole time, which can severely impact the website
while it is running. When considering whether or not to run optimize,
consider the workload of transactions that your server will process
as InnoDB tables do not suffer from fragmentation in the same way
that MyISAM tables do.

.. _MySQL manual: https://dev.mysql.com/doc/refman/5.6/en/optimize-table.html


.. index:: ext:scheduler, Database
