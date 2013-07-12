=======================================================================
Feature: #20194 - Configuration for displaying the "save & view" button
=======================================================================

Description
===========

The "save & view" button is configurable by TSConfig "TCEMAIN.preview.disableButtonForDokType" (CSV of "doktype" IDs) to disable the button for custom page "doktype"s. The default value is set in the PHP implementation: "254, 255, 199" (Storage Folder, Recycler and Menu Seperator)


Impact
======

The "save & view" button is not displayed in folders and recycler pages anymore.
