.. include:: /Includes.rst.txt

.. _feature-104095-1718283782:

=============================================================================
Feature: #104095 - Edit specific columns of multiple files in Filelist module
=============================================================================

See :issue:`104095`

Description
===========

Using the "Show columns" action in the :guilabel:`File > Filelist`
backend module allows to select the columns to be displayed file and
 folder listing.

When selecting multiple files, it has already been possible to edit the
metadata of all those records at once, using the "Edit Metadata" button
above the listing.

Now, a new button "Edit selected columns" has been introduced, which
additionally allows to access the editing form for the selected files
with just the columns of the current selection (based on "Show columns").
This improves the usability when doing mass editing of specific columns.


Impact
======

It's now possible to edit selected columns of multiple file metadata in the
:guilabel:`File > Filelist` backend module, using the new
"Edit selected columns" button.

.. index:: Backend, ext:filelist
