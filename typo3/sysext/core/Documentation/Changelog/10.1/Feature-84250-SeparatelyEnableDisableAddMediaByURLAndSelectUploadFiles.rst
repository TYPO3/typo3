.. include:: /Includes.rst.txt

============================================================================================
Feature: #84250 - Separately enable / disable "Add media by URL" and "Select & upload files"
============================================================================================

See :issue:`84250`

Description
===========

A new appearance property "fileByUrlAllowed" is used to separately enable / disable the buttons "Add media by URL" and "Select & upload files".

*  :php:`fileUploadAllowed = false` now only hides the button "Select & upload files".
*  :php:`fileByUrlAllowed = false` now hides the button "Add media by URL".

If "elementBrowserType" is set to "file" both values are true by default.

Example

.. code-block:: php

   $GLOBALS['TCA']['pages']['columns']['media']['config']['appearance'] = [
      'fileUploadAllowed' => false,
      'fileByUrlAllowed' => false,
   ];

This will suppress both buttons and only leave "Create new relation".

Impact
======

Users have to use the new appearance property "fileByUrlAllowed" to hide the button "Add media by URL"

.. index:: Backend, TCA, ext:backend
