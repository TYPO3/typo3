.. include:: /Includes.rst.txt

.. _breaking-102020-1695429353:

==========================================================
Breaking: #102020 - Removed legacy setting 'GFX/gdlib_png'
==========================================================

See :issue:`102020`

Description
===========

`GFX/gdlib_png` is a setting that adjusted rendering of temporary images
used by GDLib to be PNG files instead of GIF files.

PNG files offer many benefits over GIF files, one of them being faster
processing times using Image/GraphicsMagick.

In line with this change, the property :php:`GraphicalFunctions::$gifExtension` has
been removed, as it mainly was used by this class and :php:`GifBuilder` to determine
if a temporary PNG or GIF image should be rendered.

`GFX/processor_colorspace` now defaults to an empty value and is migrated to one if
you use the recommended colorspace for the given processor (`sRGB` for ImageMagick,
`RGB` for GraphicsMagick). Image processing now will pick the recommended colorspace
unless you configure it to be another one.

Additionally, all GIF assets that are now not shown anymore due to those changes have been
removed as well:

* `EXT:core/Resources/Public/Images/NotFound.gif`
* `EXT:install/Resources/Public/Images/TestReference/Gdlib-*.gif`

Impact
======

Temporary layers/masks are now saved as PNG files instead of GIF files.


Affected installations
======================

Every instance that already didn't set `gdlib_png` to true. Output differences may
only occur on instances that use :typoscript:`GIFBUILDER` functionality (see Migration
section for more information).


Migration
=========

The configuration value has been removed without replacement. `GFX/processor_colorspace` is
automatically migrated to the recommended value for setups using the default configuration.

:php:`GraphicalFunctions::$gifExtension` has been removed without replacement. If this has been
used to determine what type of file should be rendered using :php:`GraphicalFunctions::imageMagickConvert`,
please specify the filetype manually now.


.. index:: LocalConfiguration, NotScanned, ext:core
