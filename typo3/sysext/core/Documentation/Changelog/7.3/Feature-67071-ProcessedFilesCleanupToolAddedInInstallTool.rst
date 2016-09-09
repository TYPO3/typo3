
.. include:: ../../Includes.txt

====================================================================
Feature: #67071 - Processed files cleanup tool added in Install Tool
====================================================================

See :issue:`67071`

Description
===========

The Install Tool now provides a new tool to remove processed files (e.g. image thumbnails) from FAL in its "Clean up"
section.

The tool is useful if you change graphic-related settings or after updating GraphicsMagick/ImageMagick on the server
and you want all files to be regenerated.
