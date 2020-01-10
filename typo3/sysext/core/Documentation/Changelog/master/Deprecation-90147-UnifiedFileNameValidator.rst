.. include:: ../../Includes.txt

=================================================
Deprecation: #90147 - Unified File Name Validator
=================================================

See :issue:`90147`

Description
===========

The logic for validating if a new (uploaded) or renamed file's name is allowed, is now available in an encapsulated PHP class :php:`FileNameValidator`.

The functionality is moved so all logic is encapsulated in one single place:
- PHP constant `FILE_DENY_PATTERN_DEFAULT` is migrated into a class constant.
- LocalConfiguration setting is only used when it differs from the default.
- The GeneralUtility method is deprecated and calls `FileNameValidator->isValid()` directly.

This optimization helps to only utilize and use PHP's memory if
needed, and avoids to define run-time constants or variables,
but only initializes the logic when needed - e.g. when uploading files or using TYPO3's importer via EXT:impexp.

In addition, the PHP constant :php:`PHP_EXTENSIONS_DEFAULT` which is not
in use anymore, is marked as deprecated as well.


Impact
======

Using the method :php:`GeneralUtility::verifyFilenameAgainstDenyPattern()` directly will trigger a deprecation message.

Using the constants will continue to work but will be removed TYPO3 v11.0.

The system-wide setting to override the default file deny pattern, called :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']` is only set when different from the systems' default. If it is the same, the option is not set anymore by TYPO3 Core.


Affected Installations
======================

TYPO3 installations with PHP code calling the mentioned method directly or using one of the global constants directly.


Migration
=========

Instead of calling

:php:`GeneralUtility::verifyFilenameAgainstDenyPattern($filename)`

use

:php:`GeneralUtility::makeInstance(FileNameValidator::class)->isValid($filename);`

Instead of using the constant :php:`FILE_DENY_PATTERN_DEFAULT` use :php:`FileNameValidator::DEFAULT_FILE_DENY_PATTERN`.

For the PHP constant :php:`PHP_EXTENSIONS_DEFAULT` there is no replacement, as it has no benefit for TYPO3 Core anymore.

The extension scanner will detect the method calls or the usage of the constants.

.. index:: LocalConfiguration, PHP-API, FullyScanned, ext:core