.. include:: /Includes.rst.txt

.. _feature-104085-1718271935:

===========================================================================
Feature: #104085 - Edit specific columns of multiple records in List module
===========================================================================

See :issue:`104085`

Description
===========

Using the "Show columns" button on a record table in the :guilabel:`Web > List`
backend module allows to select the columns to be displayed for the
corresponding table listing.

When selecting multiple records, it has already been possible to edit all
those records at once, using the "Edit" button in the table header.

Now, a new button "Edit columns" has been introduced, which additionally
allows to access the editing form for the selected records with just the
columns of the current selection (based on "Show columns"). This improves
the usability when doing mass editing of specific columns.


Impact
======

It's now possible to edit the columns of multiple records in the
:guilabel:`Web > List` backend module, using the new "Edit columns" button.

.. index:: Backend, ext:backend
