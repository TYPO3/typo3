.. include:: /Includes.rst.txt

.. _breaking-82768:

=======================================================================
Breaking: #82768 - Configuration Options for Image Manipulation PHP API
=======================================================================

See :issue:`82768`

Description
===========

The main PHP class :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions` for rendering images based on ImageMagick/GraphicsMagick
and/or GDlib has been cleaned up in order to optimize various places within the code itself,
making more use of the proper "init()" function setting all relevant options.

The following previously public properties are therefore either set to "protected"
or removed/renamed as part of the streaming process, removing the possibility to
override any of the settings other than via the :php:`init()` method within
GraphicalFunctions:

* :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->gdlibExtensions`
* :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->imageFileExt`
* :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->webImageExt`
* :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->NO_IM_EFFECTS`
* :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->NO_IMAGE_MAGICK`
* :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->mayScaleUp`
* :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->dontCompress`
* :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->dontUnlinkTempFiles`
* :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->absPrefix`
* :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->im5fx_blurSteps`
* :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->im5fx_sharpenSteps`
* :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->pixelLimitGif`
* :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->colMap`
* :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->csConvObj`
* :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->jpegQuality`
* :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->OFFSET`

Additionally, the option to disable the deletion of tempFiles have been removed.

The global configuration option :php:`$TYPO3_CONF_VARS[GFX][processor_effects]`
is a boolean option now.


Impact
======

Setting any of the PHP properties above will have no effect anymore.


Affected Installations
======================

Any TYPO3 installation with an extension accessing directly GraphicalFunctions or GifBuilder API
via PHP and using any of the properties above.


Migration
=========

Ensure all options are properly set when calling :php-short:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->init()` and remove
all calls to get or set values from the previously public properties.

.. index:: LocalConfiguration, PHP-API, NotScanned
