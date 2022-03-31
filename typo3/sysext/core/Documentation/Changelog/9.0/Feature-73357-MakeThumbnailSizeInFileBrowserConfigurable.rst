.. include:: /Includes.rst.txt

==================================================================
Feature: #73357 - Make thumbnail size in file browser configurable
==================================================================

See :issue:`73357`

Description
===========

The default size of thumbnails in the file list is 64x64. These values can now be configured with UserTSConfig.

Example:

.. code-block:: typoscript

   options.file_list.thumbnail.width = 256
   options.file_list.thumbnail.height = 256


Impact
======

All preview images in the file list will be rendered in the configured thumbnail size.

.. index:: Backend, TSConfig
