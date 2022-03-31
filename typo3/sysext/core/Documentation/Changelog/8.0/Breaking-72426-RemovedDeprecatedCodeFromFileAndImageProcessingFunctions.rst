
.. include:: /Includes.rst.txt

===================================================================================
Breaking: #72426 - Removed deprecated code from file and image processing functions
===================================================================================

See :issue:`72426`

Description
===========

The following deprecated methods have been removed:

* `LocalImageProcessor::getTemporaryImageWithText`
* `ResourceCompressor::compressCssPregCallback`
* `FileList::getButtonsAndOtherMarkers`
* `GraphicalFunctions::pngToGifByImagemagick`

The following deprecated data members have been removed:

* `DuplicationBehavior::$legacyValueMap`
* `ExtendeFileUtillity::$dontCheckForUnique`
* `FileListController::$MCONF`


Impact
======

Using the methods or variables above directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use custom calls to LocalImageProcessor, ResourceCompressor, FileList, GraphicalFunctions via the methods above, or use one of the variables mentioned above.


Migration
=========

`LocalImageProcessor::getTemporaryImageWithText` use `\TYPO3\CMS\Core\Imaging\GraphicalFunctions::getTemporaryImageWithText()` instead
`ResourceCompressor::compressCssPregCallback` no replacement, functionality is implemented in a different way
`ExtendeFileUtillity::$dontCheckForUnique` use `setExistingFilesConflictMode(DuplicationBehavior::REPLACE)` instead
`FileListController::$MCONF` no replacement, configuration is done when registering the module in ext_tables.php
`FileList::getButtonsAndOtherMarkers` buttons are now defined in FileListController
`GraphicalFunctions::pngToGifByImagemagick` no replacement, the png_to_gif option has been removed

.. index:: PHP-API, Frontend, Backend
