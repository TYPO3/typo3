.. include:: ../../Includes.txt

=============================================================
Breaking: #84877 - Methods of localization repository changed
=============================================================

See :issue:`84877`

Description
===========

The method :php:`\TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository::getUsedLanguagesInPageAndColumn()`
has been renamed to :php:`\TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository::getUsedLanguagesInPage()`.

The signatures of the following methods in :php:`\TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository` have changed:

- :php:`fetchOriginLanguage`
- :php:`getLocalizedRecordCount`
- :php:`fetchAvailableLanguages`
- :php:`getRecordsToCopyDatabaseResult`

In every method, the second argument :php:`$colPos` has been removed.


Impact
======

Calling the method :php:`getUsedLanguagesInPageAndColumn()` will trigger a fatal error.

Calling the methods with the former argument for :php:`$colPos` in place will result in broken query results or fatal
errors.


Affected Installations
======================

Every 3rd party extension using :php:`\TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository` is affected.


Migration
=========

Change the method call from :php:`getUsedLanguagesInPageAndColumn()` to :php:`getUsedLanguagesInPage()`.

Remove the :php:`$colPos` arguments in the calls, as they are not required anymore.

.. index:: Backend, FullyScanned, ext:backend
