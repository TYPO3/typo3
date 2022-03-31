
.. include:: /Includes.rst.txt

==============================================================
Breaking: #73106 - Convert thumbnails only for non-image files
==============================================================

See :issue:`73106`

Description
===========

`$TYPO3_CONF_VARS[GFX][thumbnails_png]` must be taken into account only for non-image files.


Impact
======

`$TYPO3_CONF_VARS[GFX][thumbnails_png]` is now a boolean value. If the value is true, the processed
thumbnails that are not an image will be converted in png, otherwise these will be converted to gif.
It is not possible anymore to convert the processed thumbnails that are png or gif to jpg files.


Affected Installations
======================

Installations who have set the value of `$TYPO3_CONF_VARS[GFX][thumbnails_png]` to 2 or 3.


Migration
=========

The install tool automatically sets the value of `$TYPO3_CONF_VARS[GFX][thumbnails_png]` to true,
if it has been set to 1, 2 or 3 previously.

.. index:: LocalConfiguration
