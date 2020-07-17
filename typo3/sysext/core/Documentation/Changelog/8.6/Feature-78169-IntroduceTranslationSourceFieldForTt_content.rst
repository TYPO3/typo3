.. include:: ../../Includes.txt

=====================================================================
Feature: #78169 - Introduce "Translation Source" field for tt_content
=====================================================================

See :issue:`78169`

Description
===========

The new database field `l10n_source` for tt_content table has been introduced together with a new TCA ctrl configuration `translationSource`.
The `translationSource` field contains a uid of the record used as a translation source, no matter whether the record was translated in the free or connected mode.
The new TCA configuration `translationSource` contains column name, similar to the `transOrigPointerField`.
e.g.

.. code-block:: php

    $GLOBALS['TCA']['tt_content']['ctrl']['translationSource'] = 'l10n_source';

The new field solves few issues:

1. There was no way to detect whether a record was translated using connected mode or free mode.
   If a record has value > 0 in the `transOrigPointerField` (e.g. `l10n_parent`) field, it means it was translated using "connected mode".
   If the `transOrigPointerField` is 0 but the `translationSource` field is > 0 it means it was translated using "free mode".
   If both are 0, it means the record was not translated but created manually.

2. TYPO3 allows to use a record in non-default language as a translation source. In this case the information about the translation source was lost.
   Now, the `translationSource` field always contains a uid of the record used as a translation source.

3. In some places `origUid` (e.g. `t3_origuid`) fields were misused as a translation source. Now these places can be refactored to use the `translationSource` field.

Difference between `translationSource` and other existing fields
----------------------------------------------------------------

1. `transOrigPointerField` (e.g. `l10n_parent`) - "translation parent" - this field contains uid of the record in the *default language* representing the same content. The `translationSource` field can contain a uid of the record in non-default language.
2. `origUid` (e.g. `t3_origuid`) - "copy source" - this field contains uid of the record, current record was *copied from*. It might be equal to `translationSource` as localization is a copy internally, but often it is different.


See following test scenarios to see how data is handled in details.

- :code:`\TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular\Modify\ActionTest::localizeContent`
- :code:`\TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular\Modify\ActionTest::localizeContentFromNonDefaultLanguage`
- :code:`\TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular\Modify\ActionTest::copyContentToLanguage`
- :code:`\TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular\Modify\ActionTest::copyPage`
- :code:`\TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular\Modify\ActionTest::copyPageFreeMode`
- :code:`\TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular\Modify\ActionTest::localizePage`


Impact
======

Introduction of the new field is a base step for further refactorings, e.g.

- it opens a way to implement features like "reconnecting" free-mode translations back to the "connected mode"
- replace usage of the `t3_origuid` with the `l10n_source` where `t3_origuid` is misused for language handling purposes (e.g. in LocalizationRepository)

.. index:: Database, TCA, PHP-API
