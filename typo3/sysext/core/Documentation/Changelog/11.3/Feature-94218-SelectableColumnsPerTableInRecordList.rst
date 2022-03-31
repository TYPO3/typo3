.. include:: /Includes.rst.txt

=============================================================
Feature: #94218 - Selectable columns per table in record list
=============================================================

See :issue:`94218`

Description
===========

The Record List (commonly known from the list module) previously allowed to
select specific columns for a table at the bottom of the module via the
so-called "field selector".

This approach had several drawbacks:

*  UX-wise the selection was not directly visible for users, as the component was
   separated at the module page at the bottom
*  Only possible to select fields explicitly in the "single-table view"

Instead, this feature - the column selector - is now available at all times in
the title row of each table, regardless of the single-table-view, making it
much more appealing and prominent to use for editors.

This feature is active by default, and can be disabled via UserTSconfig per
table or completely for a specific user or usergroup.

Use cases / examples via UserTSconfig:

.. code-block:: typoscript

   # disable the column selector for tt_content
   mod.web_list.table.tt_content.displayColumnSelector = 0

   # disable the column selector completely
   mod.web_list.displayColumnSelector = 0

   # Disable the column selector everywhere except for a specific table
   mod.web_list.displayColumnSelector = 0
   mod.web_list.table.sys_category.displayColumnSelector = 1


Impact
======

The field selector at the bottom is not available anymore,
it has been replaced by a dropdown selector at the top of each table.

.. index:: Backend, TSConfig, ext:recordlist
