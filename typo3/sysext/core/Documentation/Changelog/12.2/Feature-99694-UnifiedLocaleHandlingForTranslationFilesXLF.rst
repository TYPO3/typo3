.. include:: /Includes.rst.txt

.. _feature-99694-1674552209:

=====================================================================
Feature: #99694 - Unified Locale handling for translation files (XLF)
=====================================================================

See :issue:`99694`

Description
===========

TYPO3 now internally uses a "locale" format following the
`IETF RFC 5646 language tag standard <https://www.rfc-editor.org/rfc/rfc5646.html>`__.

A locale supported by TYPO3 consists of the following parts (tags and subtags):

*   ISO 639-1 / ISO 639-2 compatible language key in lowercase (such as "fr" French, or "de" for German)
*   optionally the ISO 15924 compatible language script system (4 letter, such as "Hans" as in "zh_Hans")
*   optionally the region / country code according to ISO 3166-1 standard in upper camelcase such as "AT" for Austria.

Examples for a locale string are:

*   "en" for English
*   "pt" for Portuguese
*   "da-DK" for Danish as used in Denmark
*   "de-CH" for German as used in Switzerland
*   "zh-Hans-CN" for Chinese with the simplified script as spoken in China (mainland)

A new PHP object :php:`Locale` automatically separates each tag and subtag into
these parts.

The :php:`\TYPO3\CMS\Core\Localization\Locale` object can now be used to instantiate a new
:php:`\TYPO3\CMS\Core\Localization\LanguageService` object for translating labels.
Previously, TYPO3 used the `default` language key instead of the locale `en` to
identify the English language. Both are supported, but it is encouraged to use
`en-US` or `en-GB` with the region subtag to identify the chosen language more
precisely.


Impact
======

Example for using the :php:`Locale` class for creating a :php:`LanguageService`
object for translations:

..  code-block:: php

    $languageService = $languageServiceFactory->create(new Locale('de-AT'));
    $myTranslatedString = $languageService->sL(
        'LLL:EXT:my_extension/Resources/Private/Language/myfile.xlf:my-label'
    );

Using this service is highly recommended, as the wrappers
:php:`$GLOBALS['LANG']->sL()` and :php:`$GLOBALS['TSFE']->sL()` will be
deprecated in the future.

.. index:: PHP-API, ext:core
