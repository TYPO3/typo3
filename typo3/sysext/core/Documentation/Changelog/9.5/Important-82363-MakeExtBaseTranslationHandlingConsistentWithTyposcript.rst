.. include:: ../../Includes.txt

================================================================================
Important: #82363 - Make Extbase translation handling consistent with TypoScript
================================================================================

See :issue:`82363`

Description
===========

Extbase now renders the translated records in the same way TypoScript rendering does.
The new behaviour is controlled by the Extbase feature switch :typoscript:`consistentTranslationOverlayHandling`.

.. code-block:: typoscript

     config.tx_extbase.features.consistentTranslationOverlayHandling = 1

The new behaviour is default in TYPO3 v9. The feature switch will be removed in TYPO3 v10, so there will be just
one way of fetching records.
You can override the setting using normal TypoScript.


Impact
======

Users relying on the old behaviour can disable the feature switch.

The change modifies how Extbase interprets the TypoScript settings
:ts:`config.sys_language_mode` and :ts:`config.sys_language_overlay` and the
:php:`Typo3QuerySettings` properties :php:`languageOverlayMode` and :php:`languageMode`.

Changes in the rendering:

1) Setting :php:`Typo3QuerySettings->languageMode` does **not** influence how Extbase queries records anymore.
   The corresponding TypoScript setting :ts:`config.sys_language_mode` is used by the core
   to decide what to do when a page is not translated to the given language (display 404, or try page with different language).
   Users who used to set :php:`Typo3QuerySettings->languageMode` to `strict` should use
   :php:`Typo3QuerySettings->setLanguageOverlayMode('hideNonTranslated')` to get translated records only.

   The old behavior was confusing, because `languageMode` had different meaning and accepted different
   values in TS context and in Extbase context.

2) Setting :php:`Typo3QuerySettings->languageOverlayMode` to :php:`true` makes Extbase fetch records
   from default language and overlay them with translated values. So e.g. when a record is hidden in
   the default language, it will not be shown. Also records without translation parents will not be shown.
   For relations, Extbase reads relations from a translated record (so it’s not possible to inherit
   a field value from translation source) and then passes the related records through :php:`$pageRepository->getRecordOverlay()`.
   So e.g. when you have a translated `tt_content` with FAL relation, Extbase will show only those
   `sys_file_reference` records which are connected to the translated record (not caring whether some of
   these files have `l10n_parent` set).

   Previously :php:`Typo3QuerySettings->languageOverlayMode` had no effect.
   Extbase always performed an overlay process on the result set.

3) Setting :php:`Typo3QuerySettings->languageOverlayMode` to :php:`false` makes Extbase fetch aggregate
   root records from a given language only. Extbase will follow relations (child records) as they are,
   without checking their `sys_language_uid` fields, and then it will pass these records through
   :php:`$pageRepository->getRecordOverlay()`.
   This way the aggregate root record's sorting and visibility doesn't depend on default language records.
   Moreover, the relations of a record, which are often stored using default language uids,
   are translated in the final result set (so overlay happens).

   For example:
   Given a translated `tt_content` having relation to 2 categories (in the mm table translated
   tt_content record is connected to category uid in default language), and one of the categories is translated.
   Extbase will return a `tt_content` model with both categories.
   If you want to have just translated category shown, remove the relation in the translated `tt_content`
   record in the TYPO3 Backend.

Note that by default :php:`Typo3QuerySettings` uses the global TypoScript configuration like
:ts:`config.sys_language_overlay` and :php:`$GLOBALS['TSFE']->sys_language_content`
(calculated based on :ts:`config.sys_language_uid` and :ts:`config.sys_language_mode`).
So you need to change :php:`Typo3QuerySettings` manually only if your Extbase code should
behave different than other `tt_content` rendering.

Setting :php:`setLanguageOverlayMode()` on a query influences **only** fetching of the aggregate root. Relations are always
fetched with :php:`setLanguageOverlayMode(true)`.

When querying data in translated language, and having :php:`setLanguageOverlayMode(true)`, the relations
(child objects) are overlaid even if aggregate root is not translated.
See :php:`QueryLocalizedDataTest->queryFirst5Posts()`.

Following examples show how to query data in Extbase in different scenarios, independent of the global TS settings:

1) Fetch records from the language uid=1 only, with no overlays.
   Previously (:ts:`consistentTranslationOverlayHandling = 0`):

   It was not possible.


   Now (:ts:`consistentTranslationOverlayHandling = 1`):

   ::

      $querySettings = $query->getQuerySettings();
      $querySettings->setLanguageUid(1);
      $querySettings->setLanguageOverlayMode(false);

2) Fetch records from the language uid=1, with overlay, but hide non-translated records
   Previously (:ts:`consistentTranslationOverlayHandling = 0`):

   ::

      $querySettings = $query->getQuerySettings();
      $querySettings->setLanguageUid(1);
      $querySettings->setLanguageMode('strict');

   Now (:ts:`consistentTranslationOverlayHandling = 1`):

   ::

      $querySettings = $query->getQuerySettings();
      $querySettings->setLanguageUid(1);
      $querySettings->setLanguageOverlayMode('hideNonTranslated');


+------------------------+-------------------------------------------------------------------------------------------------+----------------------------------------------+------------------------------+
| QuerySettings property | old behaviour                                                                                   | new behaviour                                | default value (TSFE|Extbase) |
+========================+=================================================================================================+==============================================+==============================+
| languageUid            |                                                                                                 | same                                         | 0                            |
+------------------------+-------------------------------------------------------------------------------------------------+----------------------------------------------+------------------------------+
| respectSysLanguage     |                                                                                                 | same                                         | `true`                       |
+------------------------+-------------------------------------------------------------------------------------------------+----------------------------------------------+------------------------------+
| languageOverlayMode    | not used                                                                                        | values: `true`, `false`, `hideNonTranslated` | 0 | `true`                   |
|                        |                                                                                                 |                                              |                              |
+------------------------+-------------------------------------------------------------------------------------------------+----------------------------------------------+------------------------------+
| languageMode           | documented values: `null`, `content_fallback`, `strict` or `ignore`.                            | not used                                     | `null`                       |
|                        | Only `strict` was evaluated. Setting `LanguageMode` to `strict`                                 |                                              |                              |
|                        | caused passing `hideNonTranslated` param to `getRecordOverlay` in :php:`Typo3DbBackend`         |                                              |                              |
|                        | and changing the query to work similar to TypoScript `sys_language_overlay = hideNonTranslated` |                                              |                              |
+------------------------+-------------------------------------------------------------------------------------------------+----------------------------------------------+------------------------------+


Identifiers
-----------

Domain models have a main identifier `uid` and two additional properties `_localizedUid` and `_versionedUid`.
Depending on whether the `languageOverlayMode` mode is enabled (`true` or `'hideNonTranslated'`) or disabled (`false`),
the identifier contains different values.
When `languageOverlayMode` is enabled then `uid` property contains `uid` value of the default language record,
the `uid` of the translated record is kept in the `_localizedUid`.

+----------------------------------------------------------+-------------------------+---------------------------+
| Context                                                  | Record in language 0    | Translated record         |
+==========================================================+=========================+===========================+
| Database                                                 | uid:2                   | uid:11, l10n_parent:2     |
+----------------------------------------------------------+-------------------------+---------------------------+
| Domain Object values with `languageOverlayMode` enabled  | uid:2, _localizedUid:2  | uid:2, _localizedUid:11   |
+----------------------------------------------------------+-------------------------+---------------------------+
| Domain Object values with `languageOverlayMode` disabled | uid:2, _localizedUid:2  | uid:11, _localizedUid:11  |
+----------------------------------------------------------+-------------------------+---------------------------+

See tests in :file:`extbase/Tests/Functional/Persistence/QueryLocalizedDataTest.php`.

The :php:`$repository->findByUid()` (or :php:`$persistenceManager->getObjectByIdentifier()`) method takes current
rendering language into account (e.g. L=1). It does not take `defaultQuerySetting` set on the repository into account.
This method always performs an overlay.
Values in braces show previous behaviour (disabled flag) if different than current.

The bottom line is that with the feature flag on, you can now use  :php:`findByUid()` using translated record uid to get
translated content independently from language set in global context.

+-------------------+----------------+----------------------+----------------------+----------------------+----------------------+
|                   |                | L=0                                         | L=1                                         |
+-------------------+----------------+----------------------+----------------------+----------------------+----------------------+
| repository method | property       | Overlay              | No overlay           | Overlay              | No overlay           |
+===================+================+======================+======================+======================+======================+
| findByUid(2)      | title          | Post 2               | Post 2               | Post 2 - DK          | Post 2 - DK          |
+-------------------+----------------+----------------------+----------------------+----------------------+----------------------+
|                   | uid            | 2                    | 2                    | 2                    | 2                    |
+-------------------+----------------+----------------------+----------------------+----------------------+----------------------+
|                   | _localizedUid  | 2                    | 2                    | 11                   | 11                   |
+-------------------+----------------+----------------------+----------------------+----------------------+----------------------+
| findByUid(11)     | title          | Post 2 - DK (Post 2) | Post 2 - DK (Post 2) | Post 2 - DK          | Post 2 - DK          |
+-------------------+----------------+----------------------+----------------------+----------------------+----------------------+
|                   | uid            | 2                    | 2                    | 2                    | 2                    |
+-------------------+----------------+----------------------+----------------------+----------------------+----------------------+
|                   | _localizedUid  | 11 (2)               | 11 (2)               | 11                   | 11                   |
+-------------------+----------------+----------------------+----------------------+----------------------+----------------------+

.. note::

   Note that :php:`$repository->findByUid()` internally sets :php:`respectSysLanguage(false)` so it behaves differently
   than a regular query by an `uid` like :php:`$query->matching($query->equals('uid', 11));`
   The regular query will return :php:`null` if passed `uid` doesn't match
   the language set in the :php:`$querySettings->setLanguageUid()` method.

Filtering & sorting
-------------------

When filtering by aggregate root property like `Post->title`,
both filtering and sorting takes translated values into account and you will get correct results, also with pagination.

When filtering or ordering by child object property, then Extbase does a left join between aggregate root
table and child record table.
Then the filter is applied as where clause. This means that filtering or ordering by child record property
only takes values from child records which uids are stored in db (in most cases its default language record).
See :php:`TranslationTest::fetchingTranslatedPostByBlogTitle()`

This limitation also applies to Extbase with feature flag being disabled.

Summary of the important code changes
=====================================

1) :php:`DataMapper` gets a :php:`Query` as a constructor parameter. This allows to use aggregate root :php:`QuerySettings` (language)
   when fetching child records/relations. Later, in a separate patch we can pass other settings too e.g. :php:`setIgnoreEnableFields`
   to fix issue around this setting. See :php:`DataMapper->getPreparedQuery` method.
2) :php:`DataMapper` is passed to  :php:`LazyLoadingProxy` and  :php:`LazyObjectStorage`, so the settings don't get lost when fetching data lazily.
3) :php:`Query` object gets a new property `parentQuery` which is useful to detect whether we're fetching aggregate root or child object.
4) Extbase model for  :php:`FileReference` uses `_localizedUid` for fetching `OriginalResource`
5) :php:`DataMapper` forces child records to be fetched using  :php:`setLanguageOverlayMode(true)`.
6) When getRespectSysLanguage is set,  :php:`DataMapper` uses aggregate root language to overlay child records to correct language.
7) The `where` clause used for finding translated records in overlay mode (`true`, `hideNonTranslated`) has been fixed.
   It filters out the non translated records on db side in case `hideNonTranslated` is set.
   It allows for filtering and sorting by translated values. See :php:`Typo3DbQueryParser->getLanguageStatement()`


Most important known issues (ones this patch doesn't solve)
===========================================================

- Persistence session uses the same key for default language record and the translation - https://forge.typo3.org/issues/59992
- Extbase allows to fetch deleted/hidden records - https://forge.typo3.org/issues/86307


For more information about rendering please refer to the TypoScript reference_.

.. _reference: https://docs.typo3.org/typo3cms/TyposcriptReference/Setup/Config/Index.html?highlight=sys_language_mode#sys-language-overlay

.. index:: Database, TCA, TypoScript, ext:extbase
