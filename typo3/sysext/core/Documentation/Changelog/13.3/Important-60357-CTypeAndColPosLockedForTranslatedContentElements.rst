.. include:: /Includes.rst.txt

.. _important-60357-1777301944:

==================================================================
Important: #60357 - CType and colPos locked for translated content
==================================================================

See :issue:`60357`

Description
===========

Starting with TYPO3 v13.3, the fields :sql:`CType` and :sql:`colPos` of
connected :sql:`tt_content` translations are locked to the values of their
default-language parent. Both fields are now configured with
:php:`'l10n_mode' => 'exclude'` and
:php:`'l10n_display' => 'defaultAsReadonly'`
in :file:`EXT:frontend/Configuration/TCA/tt_content.php`.

This prevents editors from accidentally assigning a different content element
type or column position to a translated record, which previously caused silent
rendering inconsistencies: when the default-language record changed its
:sql:`CType`, the translated overlay would keep the old type and could render
incorrectly or not at all.

Impact
======

In the TYPO3 backend, the :guilabel:`Type` and :guilabel:`Column` selectors
are now read-only when editing a translated content element in connected
translation mode.

An upgrade wizard (:php:`synchronizeColPosAndCTypeWithDefaultLanguage`)
is provided to synchronize connected :sql:`tt_content` translations
whose :sql:`CType` or :sql:`colPos` differs from their default-language
parent.

.. warning::

   The upgrade wizard **overwrites** :sql:`CType` and :sql:`colPos` on
   every connected translation that currently differs from its parent —
   including records where the difference was intentional. Back up the
   database and review affected records before executing the wizard.

Extensions or integrations with connected translations that deliberately
use different :sql:`CType` values should align their content to use the
same :sql:`CType` across languages.

.. index:: Database, TCA, ext:frontend
