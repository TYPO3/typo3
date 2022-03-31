.. include:: /Includes.rst.txt

================================================================
Feature: #86670 - Make default action in DragUploader adjustable
================================================================

See :issue:`86670`

Description
===========

It is now possible to configure the default action for DragUploader in the file list module using User TSConfig.

.. code-block:: typoscript

   # Set default to replace:
   options.file_list.uploader.defaultAction = replace

   # Set default to rename:
   options.file_list.uploader.defaultAction = rename

   # Set default to cancel (cancel is also the default and set the option to skip):
   options.file_list.uploader.defaultAction = cancel


.. index:: Backend, TSConfig, ext:filelist
