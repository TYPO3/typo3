.. include:: ../../Includes.txt

================================================================
Feature: #94374 - Create new filemount via folders' context menu
================================================================

See :issue:`94374`

Description
===========

The :php:`sys_filemounts` records are an important feature, which
allows administrators to restrict their users to specific folders
in a file storage.

The workflow however was always to first create the folder in the
filelist module and afterwards switch to the list module to create
a new :php:`sys_filemounts` record for this folder. This furthermore
always required the administrator to select both, the storage and
the previously created folder, in the new record.

To ease the use for administrators, the context menu of folders is
extended with a new option "New Filemount". Using this option opens
the FormEngine with a new :php:`sys_filemounts` record, having the
correct storage and folder prefilled.

Impact
======

It's now possible to create new filemounts directly in the filelist
module, using the new "New Filemount" option in the folders' context
menu. This option also prefills the new record with the correct storage
and folder values.

.. index:: Backend, ext:filelist
