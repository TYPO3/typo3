.. include:: /Includes.rst.txt

====================================================
Feature: #48013 - Add support for progressive images
====================================================

See :issue:`48013`

Description
===========

It is now possible to generate progressive images by setting `$GLOBALS['TYPO3_CONF_VARS'][GFX][processor_interlace]` in
the Settings Module.

The possible values to set are identical to the ones in defined in the GM / IM manuals.

Possible values by the time of writing are:

* None
* Line
* Plane
* Partition

.. index:: Frontend, Backend
