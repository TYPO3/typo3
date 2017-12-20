
.. include:: ../../Includes.txt

=======================================================================
Feature: #20194 - Configuration for displaying the "Save & View" button
=======================================================================

See :issue:`20194`

Description
===========

The "Save & View" button is configurable by TSConfig "TCEMAIN.preview.disableButtonForDokType" (CSV of "doktype" IDs) to
disable the button for custom page "doktypes". The default value is set in the PHP implementation: "254, 255, 199"
(Storage Folder, Recycler and Menu Seperator)


Impact
======

The "Save & View" button is no longer displayed in folders and recycler pages.


.. index:: TSConfig, Backend
