.. include:: /Includes.rst.txt

.. _deprecation-88473:

=================================================================
Deprecation: #88473 - TypoScriptFrontendController->settingLocale
=================================================================

See :issue:`88473`

Description
===========

Due to Site Handling, setting the locale information (:php:`setlocale`) can be handled
much earlier without any dependencies on the global :php:`TSFE` object.

The functionality of the method :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->settingLocale()` has
been moved into :php-short:`\TYPO3\CMS\Core\Localization\Locales::setSystemLocaleFromSiteLanguage()`. The former method
has been marked as deprecated.


Impact
======

Calling :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->settingLocale()` will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with a third party extension booting up a custom Frontend system and
explicitly calling the method.


Migration
=========

Migrate the existing PHP code to :php-short:`\TYPO3\CMS\Core\Localization\Locales::setSystemLocaleFromSiteLanguage()` or ensure
that the SiteResolver middleware for Frontend Requests is executed where the locale is now
set automatically.

.. index:: Frontend, PHP-API, FullyScanned
