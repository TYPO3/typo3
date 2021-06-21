.. include:: ../../Includes.txt

============================================================
Feature: #94452 - Improved Multi-Selection in File Selection
============================================================

See :issue:`94452`

Description
===========

The File Selector, which is used in TYPO3 Backend, to choose one
or multiple files to be connected via `sys_file_reference` in
FormEngine, has been improved to have a better visual option
when selecting multiple records.

Previously, there was a checkbox button at the end of each file row. The
checkbox is now re-ordered and moved to the beginning
of each row and is now based on our TYPO3 Icon Set.

In addition, the view is more compact and when selecting multiple
items there is an option to select all items, no items or to toggle the
selection. The "Import selection" button now has a visual text next to
the icon, making it clearer what this button does.


Impact
======

Selection of files is now quicker to grasp for editors working
with files.

.. index:: Backend, ext:backend
