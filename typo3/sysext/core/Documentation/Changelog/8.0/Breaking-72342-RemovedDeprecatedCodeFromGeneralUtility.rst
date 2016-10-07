
.. include:: ../../Includes.txt

==============================================================
Breaking: #72342 - Removed deprecated code from GeneralUtility
==============================================================

See :issue:`72342`

Description
===========

The following methods have been removed from `GeneralUtility`

`gif_compress()`
`png_to_gif_by_imagemagick()`
`read_png_gif()`
`modifyHTMLColor()`
`modifyHTMLColorAll()`
`isBrokenEmailEnvironment()`
`normalizeMailAddress()`
`formatForTextarea()`
`inArray()`
`removeArrayEntryByValue()`
`keepItemsInArray()`
`addSlashesOnArray()`
`stripSlashesOnArray()`
`slashArray()`
`remapArrayKeys()`
`array_merge()`
`arrayDiffAssocRecursive()`
`naturalKeySortRecursive()`
`getThisUrl()`
`readLLfile()`
`quoted_printable()`
`encodeHeader()`
`substUrlsInPlainText()`
`cleanOutputBuffers()`


Impact
======

Using the methods above directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use calls to the methods above.


Migration
=========

For `gif_compress()` use `\TYPO3\CMS\Core\Imaging\GraphicalFunctions::gifCompress()` instead.
For `png_to_gif_by_imagemagick()` use `\TYPO3\CMS\Core\Imaging\GraphicalFunctions::pngToGifByImagemagick()` instead.
For `read_png_gif()` use `\TYPO3\CMS\Core\Imaging\GraphicalFunctions::readPngGif()` instead.
For `inArray()` use `ArrayUtility::inArray()` instead.
For `removeArrayEntryByValue()` use `ArrayUtility::removeArrayEntryByValue()` instead.
For `keepItemsInArray()` use `ArrayUtility::keepItemsInArray()` instead.
For `remapArrayKeys()`  use `ArrayUtility::remapArrayKeys()` instead.
For `array_merge()` use native php '+' operator instead.
For `arrayDiffAssocRecursive()` use `ArrayUtility::arrayDiffAssocRecursive()` instead.
For `naturalKeySortRecursive()` use `ArrayUtility::naturalKeySortRecursive()` instead.
For `getThisUrl()` use `GeneralUtility::getIndpEnv*` instead.
For `quoted_printable()` use mailer API instead.
For `encodeHeader()` use mailer API instead.
For `substUrlsInPlainText()` use mailer API instead.
For `cleanOutputBuffers()` use ob_* functions directly or `self::flushOutputBuffers.`

.. index:: PHP-API
