.. include:: /Includes.rst.txt

============================================================
Important: #75400 - New DataHandler command 'copyToLanguage'
============================================================

See :issue:`75400`

Description
===========

A new DataHandler command `copyToLanguage` has been introduced. It behaves like `localize` command
(both record and child records are copied to a given language), but does not set `transOrigPointerField` fields (e.g. l10n_parent).

The `copyToLanguage` command should be used when localizing records in "Free Mode". This command is used when localizing
content elements using the translation wizard's "Copy" strategy.

The `localize` DataHandler command should be used when translating records in "Connected Mode" (strict translation of records from the default language).
This command is used when selecting "Translate" strategy in content elements translation wizard.

.. index:: PHP-API, Backend
