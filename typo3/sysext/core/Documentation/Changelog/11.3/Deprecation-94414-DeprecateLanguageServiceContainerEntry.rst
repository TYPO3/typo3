.. include:: /Includes.rst.txt

=====================================================
Deprecation: #94414 - LanguageService container entry
=====================================================

See :issue:`94414`

Description
===========

Instances of :php:`TYPO3\CMS\Core\Localization\LanguageService` require
custom initialization with a language key and additionally depend on Core services.
:php:`TYPO3\CMS\Core\Localization\LanguageServiceFactory` has therefore
previously been introduced in order to manage this initialization.
This replaced prior used instantiation via
:php:`TYPO3\CMS\Core\Localization\LanguageService::create()` or
:php:`GeneralUtility::makeInstance(LanguageService::class)`.


Impact
======

Injecting :php:`TYPO3\CMS\Core\Localization\LanguageService` or creating
instances via :php:`GeneralUtility::makeInstance(LanguageService::class)`,
:php:`LanguageService::create()`, :php:`LanguageService::createFromUserPreferences()`
or :php:`LanguageService::createFromSiteLanguage()` will trigger a
PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Extensions injecting :php:`TYPO3\CMS\Core\Localization\LanguageService`
or creating custom instances via :php:`GeneralUtility::makeInstance(LanguageService::class)`
or :php:`TYPO3\CMS\Core\Localization\LanguageService::create()`.

This is relatively unlikely since most usages are bootstrap related and
extensions usually access the prepared LanguageService via :php:`GLOBALS['LANG']`
in normal cases.

Usages of :php:`LanguageService::create()`, :php:`LanguageService::createFromUserPreferences()`
and :php:`LanguageService::createFromSiteLanguage()` are be found by the extension scanner
as strong match.


Migration
=========

The factory :php:`TYPO3\CMS\Core\Localization\LanguageServiceFactory`
should be injected and used instead.

.. index:: Backend, PHP-API, PartiallyScanned, ext:core
