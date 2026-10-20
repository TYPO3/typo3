.. include:: /Includes.rst.txt

.. _important-88452-1792470512:

=====================================================================
Important: #88452 - Enforce origUid for non-translatable child tables
=====================================================================

See :issue:`88452`

Description
===========

A language-aware table can declare an inline or file field with
:php:`'l10n_mode' => 'exclude'` or
:php:`'behaviour' => ['allowLanguageSynchronization' => true]`. The DataHandler
keeps such a field in sync with the default language: each translation of the
parent record receives a copy of every child record.

If the child table is not language-aware (no :php:`languageField` in its
:php:`ctrl` section), the copies can only be matched to the child records of
the default language through the :php:`origUid` field of the child table.
Without it, the DataHandler deleted the copies of a translation and created
them again on every save of the parent record, in either language.

The :php:`origUid` setting is now required for such child tables. If it is
missing, TYPO3 sets :php:`$GLOBALS['TCA'][$childTable]['ctrl']['origUid']` to
:sql:`t3_origuid` while building TCA, after all
:file:`Configuration/TCA/Overrides` files have been loaded. Like any field
referenced in :php:`ctrl`, the :sql:`t3_origuid` column is added to the
database schema automatically.

Child tables which already define :php:`origUid`, or a TCA column named
:sql:`t3_origuid` for another purpose, are left untouched.

Impact
======

Affected child tables receive the TCA setting :php:`origUid` and a new
:sql:`t3_origuid` database column. Use the database analyzer in
:guilabel:`Admin Tools > Maintenance` to add the column, otherwise copying or
localizing records of such a table fails.

Copies of child records that were created before the change do not point to
their origin. They are deleted and copied once more, with new uids, on the
next save of the parent record. From then on, they are kept.

To make the configuration explicit, add the setting to the :php:`ctrl` section
of the child table:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/tx_myextension_child.php

    return [
        'ctrl' => [
            // ...
            'origUid' => 't3_origuid',
        ],
        // ...
    ];

.. index:: Database, TCA, ext:core
