.. include:: /Includes.rst.txt

====================================================
Feature: #94906 - Multi record selection in filelist
====================================================

See :issue:`94906`

Description
===========

With :issue:`94452` the file list in the file selector has been improved
by introducing an optimized way of selecting the files to attach to a record.

Those optimizations have now also been added to the filelist module. The
checkboxes, previously only used for adding files / folders to the
clipboard, are now always shown in front of each file / folder and are
now independent of the current clipboard mode. Furthermore, the
convenience actions such as "check all", "uncheck all" and "toggle
selection" are now available in the filelist, too.

By decoupling the selection from the clipboard logic, it is
now possible to directly work with the current selection without the
need to transfer it to the clipboard first. This means, editing or
deleting multiple files is now directly possible without any clipboard
interaction. The available actions appear once an element has been
selected.

As mentioned above, the "Edit marked" action has been added to the
filelist, which might already be known from the recordlist module.
This action allows to edit the :sql:`sys_file_metadata` records of
all selected files at once.

Impact
======

Selection of files and folders is now quicker to grasp for editors working
in the filelist module. It is also possible to directly
execute actions, e.g. editing metadata of selected files, without
transferring them to the clipboard first.

.. index:: Backend, ext:filelist
