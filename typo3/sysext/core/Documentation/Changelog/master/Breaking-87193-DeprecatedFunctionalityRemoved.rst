.. include:: ../../Includes.txt

===================================================
Breaking: #87193 - Deprecated functionality removed
===================================================

See :issue:`87193`

Description
===========

The following PHP classes that have been previously deprecated for v9 have been removed:

* :php:`TYPO3\CMS\Core\Encoder\JavaScriptEncoder`
* :php:`TYPO3\CMS\Core\Resource\Utility\BackendUtility`
* :php:`TYPO3\CMS\Core\Utility\ClientUtility`
* :php:`TYPO3\CMS\Core\Utility\PhpOptionsUtility`
* :php:`TYPO3\CMS\Workspaces\Service\AutoPublishService`
* :php:`TYPO3\CMS\Workspaces\Task\AutoPublishTask`
* :php:`TYPO3\CMS\Workspaces\Task\CleanupPreviewLinkTask`

The following PHP class methods that have been previously deprecated for v9 have been removed:

* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->convArray()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->convCaseFirst()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->crop()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->entities_to_utf8()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->parse_charset()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->utf8_char2byte_pos()`
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->utf8_to_entities()`

The following methods changed signature according to previous deprecations in v9 at the end of the argument list:

* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->conv()` - Fourth argument dropped

The following public class properties have been dropped:

* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->synonyms`

The following class properties have changed visibility:

* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->eucBasedSets` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->noCharByteVal` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->parsedCharsets` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->toASCII` changed from public to protected
* :php:`TYPO3\CMS\Core\Charset\CharsetConverter->twoByteSets` changed from public to protected

The following scheduler tasks have been removed:

* EXT:workspaces CleanupPreviewLinkTask
* EXT:workspaces AutoPublishTask

Impact
======

Instantiating or requiring the PHP classes, calling the PHP methods directly, will result in PHP fatal errors.

.. index:: Backend, CLI, FlexForm, Fluid, Frontend, JavaScript, LocalConfiguration, PHP-API, TCA, TSConfig, TypoScript, PartiallyScanned
