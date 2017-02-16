.. include:: ../../Includes.txt

=========================================================
Deprecation: #79858 - TSFE-related properties and methods
=========================================================

See :issue:`79858`

Description
===========

The following properties within TypoScriptFrontendController have been marked as deprecated:

`$compensateFieldWidth`
`$excludeCHashVars`
`$scriptParseTime`

The following methods have been marked as deprecated:

`TypoScriptFrontendController->generatePage_whichScript()` (used via :ts:`config.pageGenScript`)
`TypoScriptFrontendController->encryptEmail()`
`TypoScriptFrontendController->encryptCharcode()`
`PageGenerator::pagegenInit()`

The following TypoScript properties have been marked as deprecated:

`config.pageGenScript`
`config.compensateFieldWidth`


Impact
======

Calling any of the PHP methods will trigger a deprecation log entry.

All properties and options are still callable with the according output, however there are
alternatives to achieve the same.


Affected Installations
======================

Any TYPO3 installation working with custom extensions that use any of these functionalities, methods
or properties.


Migration
=========

All of the functionality is obsolete or outdated and should be handled differently from now on:

1. The `compensateFieldWidth` option was used for forms built with TYPO3 4.x (before TYPO3 4.6),
instead, any other form framework should be used for forms and for field width calculations, where
styling of form fields are also handled via CSS.

2. An alternative `config.pageGenScript` can be used and set via hooks in PHP classes nowadays and
executed, instead of configuring this functionality on a high-end TypoScript level to execute include
spaghetti PHP code within a file.

3. `PageGenerator::pagegenInit()` is solely working on public properties of the TSFE PHP class, which
belongs to the TSFE object itself (thus, the logic is copied to `$TSFE->preparePageContentGeneration()`)

4. Calculating the debug parse time for the web page is not part of the controller logic but more
certainly belongs to the request handling itself, where it is handled in a cleaner way for PHP,
waiting for further refactorings in TYPO3 v9.

5. The methods `TypoScriptFrontendController->encryptEmail()` and `encryptCharcode()` have been moved
to ContentObjectRenderer.


.. index:: Frontend, TypoScript