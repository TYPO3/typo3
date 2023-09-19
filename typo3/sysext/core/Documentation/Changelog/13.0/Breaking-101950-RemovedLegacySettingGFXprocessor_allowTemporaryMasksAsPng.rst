.. include:: /Includes.rst.txt

.. _breaking-101950-1695121128:

===================================================================================
Breaking: #101950 - Removed legacy setting 'GFX/processor_allowTemporaryMasksAsPng'
===================================================================================

See :issue:`101950`

Description
===========

`GFX/processor_allowTemporaryMasksAsPng` is a setting that stems from an even older
setting called `im_mask_temp_ext_gif`. This setting was added because generally PNG
generation of Image/GraphicsMagick is always faster than generating GIF files, but
there were issues with PNG files in earlier versions of ImageMagick 5.

TYPO3 requires newer versions of GraphicsMagick and at least ImageMagick version 6,
in which the above reported behaviours couldn't be replicated anymore, obsoleting
the need for a non-PNG setting entirely.

The following global settings have no effect anymore and are automatically removed
if still in use:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_allowTemporaryMasksAsPng']` (removed)


Impact
======

Temporarily saved masking images are now saved as PNG files rather than GIF images.

Testing has revealed no visual changes between this setting being turned on or off,
with both ImageMagick or GraphicsMagick.


Affected installations
======================

Every instance that already didn't set `processor_allowTemporaryMasksAsPng` to true.


Migration
=========

The configuration value has been removed without replacement. No migration is necessary.

.. index:: LocalConfiguration, FullyScanned, ext:core
