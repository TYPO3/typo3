..  include:: /Includes.rst.txt

..  _feature-108799-1738094060:

==================================================================================
Feature: #108799 - LocalizationRepository methods for fetching record translations
==================================================================================

See :issue:`108799`

Description
===========

TYPO3 historically has helper methods for localizations in various places.
This patch centralizes localization-related functionality by marking
:php:`\TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository`
as public (non-internal) and adding new methods as modern, DI-friendly
alternatives to the static BackendUtility methods.

getRecordTranslation()
----------------------

Fetches a single translated version of a record for a specific language.

..  code-block:: php

    public function getRecordTranslation(
        string|TcaSchema $tableOrSchema,
        int|array|RecordInterface $recordOrUid,
        int|LanguageAspect $language,
        int $workspaceId = 0,
        bool $includeDeletedRecords = false,
    ): ?RawRecord


getRecordTranslations()
-----------------------

Fetches all translations of a record. This method can also be used to count
translations by using :php:`count()` on the result, replacing the need for
:php:`BackendUtility::translationCount()`.

..  code-block:: php

    public function getRecordTranslations(
        string|TcaSchema $tableOrSchema,
        int|array|RecordInterface $recordOrUid,
        array $limitToLanguageIds = [],
        int $workspaceId = 0,
        bool $includeDeletedRecords = false,
    ): array

Returns an array of translated RawRecord objects indexed by language ID.


getPageTranslations()
---------------------

Fetches all page translations for a given page.

..  code-block:: php

    public function getPageTranslations(
        int $pageUid,
        array $limitToLanguageIds = [],
        int $workspaceId = 0,
        bool $includeDeletedRecords = false,
    ): array

Returns an array of page translation records as RawRecord objects
indexed by language ID.


Impact
======

Extension developers working with record translations in the TYPO3 Backend now
have access to modern, injectable repository methods that follow current
TYPO3 coding practices.

The legacy static methods :php:`BackendUtility::getRecordLocalization()`,
:php:`BackendUtility::getExistingPageTranslations()`, and
:php:`BackendUtility::translationCount()` remain available for backward
compatibility until migrated completely.

..  index:: Backend, PHP-API, ext:backend
