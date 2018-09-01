.. include:: ../../Includes.txt

====================================================================================================
Deprecation: #85543 - Language-related properties in TypoScriptFrontendController and PageRepository
====================================================================================================

See :issue:`85543`

Description
===========

With the introduction of a LanguageAspect within the new Context API, the following public properties
have been marked as deprecated:

* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sys_language_uid`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sys_language_content`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sys_language_contentOL`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sys_language_mode`
* :php:`TYPO3\CMS\Frontend\Page\PageRepository->sys_language_uid`

Additionally, in order to create a better abstraction, the third constructor argument of
:php:`TYPO3\CMS\Core\Utility\RootlineUtility` now expects a :php:`Context` object instead of a :php:`PageRepository`.

Impact
======

Accessing or setting one of the properties will trigger a PHP :php:`E_USER_DEPRECATED` error.

Calling RootlineUtility constructor with a PageRepository as a third argument will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any multi-lingual TYPO3 installation with custom non-Extbase-related PHP code.


Migration
=========

Use the new :php:`LanguageAspect` with various superior properties to access the various values.

.. code-block:: php

	$languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language')
	// (previously known as TSFE->sys_language_uid)
	$languageAspect->getId();
	// (previously known as TSFE->sys_language_content)
	$languageAspect->getContentId();
	// (previously known as TSFE->sys_language_contentOL)
	$languageAspect->getLegacyOverlayType();
	// (previously known as TSFE->sys_language_mode)
	$languageAspect->getLegacyLanguageMode();

Also, have a detailed look on what other properties the language aspect offers for creating fallback chains,
and more sophisticated overlays.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
