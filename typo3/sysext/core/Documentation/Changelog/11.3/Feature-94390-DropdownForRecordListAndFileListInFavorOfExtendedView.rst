.. include:: /Includes.rst.txt

==================================================================================
Feature: #94390 - Dropdown for record list and file list in favor of Extended View
==================================================================================

See :issue:`94390`

Description
===========

The option "Extended View", which was used in the TYPO3 Backend
modules :guilabel:`Web => List` and :guilabel:`File => Filelist` to show
additional icons, has been removed in favor of a dropdown with all items which is
always available.


Impact
======

This change is added as a user experience improvement over an additional
configuration option to give editors a unified experience, as
the additional menu with alternative items is common in other
web applications.

The TSconfig options `options.file_list.enableDisplayBigControlPanel`
and `mod.web_list.enableDisplayBigControlPanel` have no effect anymore,
because the checkboxes are removed.

.. index:: Backend, ext:backend
