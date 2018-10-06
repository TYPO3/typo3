
.. include:: ../../Includes.txt

========================================================
Feature: #56133 - Replace file feature for fal file list
========================================================

See :issue:`56133`

Description
===========

Now its possible to replace files for a specific record at the extended view in the FAL record list.

Impact
======

Provides a new button "replace" at the extended view in FAL equal to DAM. It's possible to replace a file

* with a new one -> old file will be overwritten; identifier of the file object will be kept
* with a new one -> old file will be deleted; identifier of the file object will be changed to the new filename

The file replacing also respects unique file names.


.. index:: FAL, Backend
