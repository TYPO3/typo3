
.. include:: ../../Includes.txt

====================================================================
Breaking: #72427 - Removed TypoScript-related methods and properties
====================================================================

See :issue:`72427`

Description
===========

The following methods and properties have been removed:

* `TYPO3\CMS\Core\TypoScript\ConfigurationForm::ext_getKeyImage()`
* `TYPO3\CMS\Core\TypoScript\ExtendedTemplateService::ext_noSpecialCharsOnLabels`
* `TYPO3\CMS\Core\TypoScript\ExtendedTemplateService::makeHtmlspecialchars()`
* `TYPO3\CMS\Core\TypoScript\ExtendedTemplateService::ext_getKeyImage()`
* `TYPO3\CMS\Core\TypoScript\TemplateService::tempPath`
* `TYPO3\CMS\Core\TypoScript\TemplateService::wrap()`
* `TYPO3\CMS\T3editor\T3editor::isEnabled()`
* `TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController::verify_TSobjects()`

The TypoScript conditions "browser", "version", "device", "system" and "useragent" have been removed.


Impact
======

Calling the methods above will result in a PHP fatal error.

Using the removed TypoScript conditions will have no effect anymore.

.. index:: PHP-API, TypoScript
