.. include:: /Includes.rst.txt

====================================================================
Feature: #69274 - Preserve image rotation if orient is saved in exif
====================================================================

See :issue:`69274`

Description
===========

Many photo cameras nowadays store pictures in the native sensor orientation.
The real orientation is stored as meta information in the EXIF data.

TYPO3 now recognizes this image orientation and uses it when reading the image dimensions and
uses this info when scaling/cropping (processing) the image.


Impact
======

Images with an orientation other than 0 degrees are now properly scaled/cropped (processed).
Thumbnails in the Backend and processed images in the Frontend are presented in the correct orientation.

.. index:: FAL, ext:core
