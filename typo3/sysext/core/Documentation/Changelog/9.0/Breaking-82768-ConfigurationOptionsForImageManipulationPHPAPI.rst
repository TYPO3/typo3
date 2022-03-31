.. include:: /Includes.rst.txt

=======================================================================
Breaking: #82768 - Configuration Options for Image Manipulation PHP API
=======================================================================

See :issue:`82768`

Description
===========

The main PHP class :php:`GraphicalFunctions` for rendering images based on ImageMagick/GraphicsMagick
and/or GDlib has been cleaned up in order to optimize various places within the code itself,
making more use of the proper "init()" function setting all relevant options.

The following previously public properties are therefore either set to "protected"
or removed/renamed as part of the streaming process, removing the possibility to
override any of the settings other than via the :php:`init()` method within
GraphicalFunctions:

* :php:`GraphicalFunctions->gdlibExtensions`
* :php:`GraphicalFunctions->imageFileExt`
* :php:`GraphicalFunctions->webImageExt`
* :php:`GraphicalFunctions->NO_IM_EFFECTS`
* :php:`GraphicalFunctions->NO_IMAGE_MAGICK`
* :php:`GraphicalFunctions->mayScaleUp`
* :php:`GraphicalFunctions->dontCompress`
* :php:`GraphicalFunctions->dontUnlinkTempFiles`
* :php:`GraphicalFunctions->absPrefix`
* :php:`GraphicalFunctions->im5fx_blurSteps`
* :php:`GraphicalFunctions->im5fx_sharpenSteps`
* :php:`GraphicalFunctions->pixelLimitGif`
* :php:`GraphicalFunctions->colMap`
* :php:`GraphicalFunctions->csConvObj`
* :php:`GraphicalFunctions->jpegQuality`
* :php:`GraphicalFunctions->OFFSET`

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
