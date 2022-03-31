.. include:: /Includes.rst.txt

==============================================================
Deprecation: #95219 - TypoScriptFrontendController->ATagParams
==============================================================

See :issue:`95219`

Description
===========

The public property :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->ATagParams`
has been marked as deprecated.

It was used in the past as a copy of the value
:php:`TypoScriptFrontendController->config[config][ATagParams]`,
which should be used instead.

There is no need to use such a (less prominent) configuration option in a
separate public property, as it needs to be kept in sync with the
actual configuration option.

The second argument of the related method
:php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getATagParams()`
called :php:`$addGlobal` is also marked as deprecated, and will have no effect
anymore in TYPO3 v12.

Impact
======

Accessing, setting or writing this property will trigger a PHP :php:`E_USER_DEPRECATED` error.

Calling :php:`ContentObjectRenderer->getATagParams()`
with a second argument set to false will trigger a PHP :php:`E_USER_DEPRECATED` error
as well.


Affected Installations
======================

TYPO3 installations with third-party-extensions accessing, or
writing this property directly within PHP, or calling :php:`getATagParams()`
directly, which is highly unlikely.


Migration
=========

All calls of :php:`$GLOBALS['TSFE']->ATagParams` can be replaced
with :php:`$GLOBALS['TSFE']->config['config']['ATagParams'] ?? ''`.

.. index:: Frontend, PHP-API, TypoScript, FullyScanned, ext:frontend
