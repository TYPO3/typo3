.. include:: ../../Includes.txt

=======================================================================
Breaking: #82768 - Configuration Options for Image Manipulation PHP API
=======================================================================

See :issue:`82768`

Description
===========

The main PHP class `GraphicalFunctions` for rendering images based on ImageMagick/GraphicsMagick
and/or GDlib has been cleaned up in order to optimize various places within the code itself,
making more use of the proper "init()" function setting all relevant options.

The following previously public properties are therefore either set to "protected"
or removed/renamed as part of the streaming process, removing the possibility to
override any of the settings other than via the `init()` method within
GraphicalFunctions:

 * GraphicalFunctions->gdlibExtensions
 * GraphicalFunctions->imageFileExt
 * GraphicalFunctions->webImageExt
 * GraphicalFunctions->NO_IM_EFFECTS
 * GraphicalFunctions->NO_IMAGE_MAGICK
 * GraphicalFunctions->mayScaleUp
 * GraphicalFunctions->dontCompress
 * GraphicalFunctions->dontUnlinkTempFiles
 * GraphicalFunctions->absPrefix
 * GraphicalFunctions->im5fx_blurSteps
 * GraphicalFunctions->im5fx_sharpenSteps
 * GraphicalFunctions->pixelLimitGif
 * GraphicalFunctions->colMap
 * GraphicalFunctions->csConvObj
 * GraphicalFunctions->jpegQuality
 * GraphicalFunctions->OFFSET

Additionally, the option to disable the deletion of tempFiles have been removed.

The global configuration option :php:`$TYPO3_CONF_VARS[GFX][processor_effects]`
is a boolean option now.


Impact
======

Setting any of the PHP properties above will have no effect anymore.


Affected Installations
======================

Any TYPO3 installation with a extension accessing directly GraphicalFunctions or GifBuilder API
via PHP and using any of the properties above.


Migration
=========

Ensure all options are properly set when calling :php:`GraphicalFunctions->init()` and remove
all calls to get or set values from the previously public properties.

.. index:: LocalConfiguration, PHP-API, NotScanned