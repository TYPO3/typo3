.. include:: /Includes.rst.txt

=================================================================
Deprecation: #88473 - TypoScriptFrontendController->settingLocale
=================================================================

See :issue:`88473`

Description
===========

Due to Site Handling, setting the locale information (:php:`setlocale`) can be handled
much earlier without any dependencies on the global :php:`TSFE` object.

The functionality of the method :php:`TypoScriptFrontendController->settingLocale()` has
been moved into :php:`Locales::setSystemLocaleFromSiteLanguage()`. The former method
has been marked as deprecated.


Impact
======

Calling :php:`TypoScriptFrontendController->settingLocale()` will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with a third party extension booting up a custom Frontend system and
explicitly calling the method.


Migration
=========

Migrate the existing PHP code to :php:`Locales::setSystemLocaleFromSiteLanguage()` or ensure
that the SiteResolver middleware for Frontend Requests is executed where the locale is now
set automatically.

.. index:: Frontend, PHP-API, FullyScanned
