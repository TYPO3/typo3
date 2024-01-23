.. include:: /Includes.rst.txt

.. _breaking-101941-1695060791:

==============================================================
Breaking: #101941 - Various GFX-related legacy options removed
==============================================================

See :issue:`101941`

Description
===========

TYPO3's powerful image manipulation suite has legacy options which were used
20 years ago where it was more important to deliver GIF files instead of PNG
files due to the size of the file.

However, PNG supports transparency and 24 bit, and is supported widely nowadays
and the preferred option.

For this reason, TYPO3's default behavior is now to generate PNG files instead
of GIF files when creating thumbnails.

In addition, the GIFBUILDER option "reduceColors" has been removed, along with
the option to additionally compress GIF files via ImageMagick or GDLib.

The following PHP code has been removed:

* :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->dontCompress`
* :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->IMreduceColors()`
* :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions::gifCompress()`

The following global settings have no effect anymore and are automatically removed
if still in use:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['gif_compress']` (removed)
* :php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails_png']` (always active)


Impact
======

When generating thumbnail images or images via GIFBUILDER from various sources which
aren't supported by TYPO3's graphical processing, a PNG is now created instead of a GIF.

This can happen, for instance, when previewing PDF files. JPG files are still kept the same.


Affected installations
======================

TYPO3 installations which used these settings or customized GifBuilder code.


Migration
=========

For 99% of the installations, these options have been activated already, so there is no change
necessary when upgrading and also no visual change.

.. index:: FAL, Frontend, LocalConfiguration, PHP-API, TypoScript, FullyScanned, ext:core
