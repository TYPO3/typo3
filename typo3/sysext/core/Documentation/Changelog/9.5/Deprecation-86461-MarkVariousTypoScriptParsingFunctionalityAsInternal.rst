.. include:: /Includes.rst.txt

===============================================================================
Deprecation: #86461 - Mark various TypoScript parsing functionality as internal
===============================================================================

See :issue:`86461`

Description
===========

The following properties and methods of class :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser`
have changed visibility from public to protected as they are used for internal purpose:

* :php:`raw`
* :php:`rawP`
* :php:`lastComment`
* :php:`commentSet`
* :php:`multiLineEnabled`
* :php:`multiLineObject`
* :php:`multiLineValue`
* :php:`inBrace`
* :php:`lastConditionTrue`
* :php:`syntaxHighLight`
* :php:`highLightData`
* :php:`highLightData_bracelevel`
* :php:`highLightStyles`
* :php:`highLightBlockStyles`
* :php:`highLightBlockStyles_basecolor`
* :php:`nextDivider()`
* :php:`parseSub()`
* :php:`rollParseSub()`
* :php:`setVal()`
* :php:`error()`
* :php:`regHighLight()`
* :php:`syntaxHighlight_print()`


Impact
======

Calling any of the methods or accessing any of the properties will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom TypoScript Parsers or extensions which make use of internal
TypoScript parsings.


Migration
=========

Ensure to only use public entry-points of the TypoScript parsers.

.. index:: TypoScript, FullyScanned, PHP-API
