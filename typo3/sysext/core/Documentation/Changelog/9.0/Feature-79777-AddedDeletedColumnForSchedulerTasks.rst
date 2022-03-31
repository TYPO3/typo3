.. include:: /Includes.rst.txt

================================================================
Feature: #79777 - EXT:scheduler - Deleted column for tasks added
================================================================

See :issue:`79777`

Description
===========

A reference to a previously deleted task is now kept in the database with a :sql:`deleted=1` flag,
in order to check historic calls on scheduler tasks.

.. index:: Backend, Database, ext:scheduler
