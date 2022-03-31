.. include:: /Includes.rst.txt

====================================================
Feature: #84184 - Show columns selection in filelist
====================================================

See :issue:`84184`

Description
===========

The column selector, introduced in :issue:`94218` and improved
in :issue:`94474`, is now also available in the filelist module.

As already known from the recordlist, it can be used to manage the fields,
displayed for each file / folder, while containing convenience actions,
such as "filter", "check all / none" and "toggle selection".

The fields to be selected are a combination of special fields, such as
`references` or `read/write` permissions, the corresponding `sys_file`
record fields, as well as all available `sys_file_metadata` fields.

Administrators can manage whether the column selection is available
for their users with a new User TSconfig option:

.. code-block:: typoscript

   # disable the column selector
   options.file_list.displayColumnSelector = 0


Impact
======

It's now possible to manage the displayed fields for files / folders
in the filelist module, using the columns selection component.

.. index:: Backend
