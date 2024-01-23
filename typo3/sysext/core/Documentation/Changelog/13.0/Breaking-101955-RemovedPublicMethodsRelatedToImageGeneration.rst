.. include:: /Includes.rst.txt

.. _breaking-101955-1695195288:

======================================================================
Breaking: #101955 - Removed public methods related to Image Generation
======================================================================

See :issue:`101955`

Description
===========

For historical reasons, there is a PHP API class
:php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions` which deals with general
imaging functionality such as converting, scaling or cropping images - mainly
with ImageMagick / GraphicsMagick as a basis. In addition, the PHP class
:php:`\TYPO3\CMS\Frontend\Imaging\GifBuilder` which works with instructions
originally built for use with TypoScript and image manipulation such as masking,
combining text with images based mainly on the PHP extension GDLib.

Even though TYPO3 works best having both GDLib and ImageMagick installed and
properly configured, the inter-dependency within the TYPO3 Core API when to
use what class has always been unclear - mostly because this
functionality has not been cleaned up in the past 20 years.

For this reason, :php:`GifBuilder` now contains all functionality related to
GDLib, and all related methods from GraphicalFunctions have been removed.
:php:`GraphicalFunctions` thus is only contains ImageMagick/GraphicsMagick
functionality.

In addition, :php:`GifBuilder` and :php:`GraphicalFunctions` are now two separate classes
without inheritance, but utilizes the Composition pattern.

The following public methods from :php:`GraphicalFunctions` have been removed:

- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->adjust()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->applyImageMagickToPHPGif()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->applyOffset()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->autolevels()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->convertColor()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->copyImageOntoImage()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->crop()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->destroy()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->getTemporaryImageWithText()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->hexColor()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->imageCreateFromFile()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->ImageTTFBBoxWrapper()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->ImageTTFTextWrapper()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->ImageWrite()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->inputLevels()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->makeBox()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->makeEffect()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->makeEllipse()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->makeEmboss()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->makeOutline()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->makeShadow()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->makeText()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->maskImageOntoImage()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->output()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->outputLevels()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->scale()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->splitString()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->unifyColors()`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions::readPngGif()`

The following public properties from :php:`GraphicalFunctions` have been removed:

- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->colMap`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->h`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->map`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->saveAlphaLayer`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->setup`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->truecolorColors`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->w`
- :php:`\TYPO3\CMS\Core\Imaging\GraphicalFunctions->workArea`

The following public properties in :php:`GifBuilder` have been removed:

- :php:`\TYPO3\CMS\Frontend\Imaging\GifBuilder->charRangeMap`
- :php:`\TYPO3\CMS\Frontend\Imaging\GifBuilder->myClassName`

The following public properties in :php:`GifBuilder` are now marked as protected:

- :php:`\TYPO3\CMS\Frontend\Imaging\GifBuilder->charRangeMap`
- :php:`\TYPO3\CMS\Frontend\Imaging\GifBuilder->combinedFileNames`
- :php:`\TYPO3\CMS\Frontend\Imaging\GifBuilder->combinedTextStrings`
- :php:`\TYPO3\CMS\Frontend\Imaging\GifBuilder->data`
- :php:`\TYPO3\CMS\Frontend\Imaging\GifBuilder->defaultWorkArea`
- :php:`\TYPO3\CMS\Frontend\Imaging\GifBuilder->objBB`
- :php:`\TYPO3\CMS\Frontend\Imaging\GifBuilder->XY`

Impact
======

When using the classes directly in PHP code of extensions, calling any of the
methods or accessing / setting the affected properties will result in a PHP
error.


Affected installations
======================

TYPO3 installations with custom extensions utilizing the PHP API of these two
classes directly.

For any usages of these classes via TypoScript or the File Abstraction Layer API
will continue to work and are not affected by this breaking change.


Migration
=========

Use static analysis tools such as PHPStan or Psalm to detect if PHP code of
custom extensions is affected, and make use of :php:`GifBuilder` class instead of
:php:`GraphicalFunctions` when needing GDLib functionality.

.. index:: PHP-API, FullyScanned, ext:core
