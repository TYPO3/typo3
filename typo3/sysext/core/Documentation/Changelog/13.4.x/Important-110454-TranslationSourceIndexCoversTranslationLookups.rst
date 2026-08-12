..  include:: /Includes.rst.txt

..  _important-110454-1786550456:

============================================================================
Important: #110454 - Translation source index covers the translation lookups
============================================================================

See :issue:`110454`

Description
===========

Looking up the translations of a record matches either the translation source
field (`l10n_source`) or - for translations written without a translation
source - the combination of an empty translation source and the translation
origin pointer field (`l18n_parent` / `l10n_parent`). Both alternatives have to
be resolvable through a single index, otherwise MySQL reduces the condition to
a range over `l10n_source IN (0, <uid>)` - which covers every record without a
translation source, usually a large part of the table - or discards the index
and scans the table.

The automatically generated `translation_source` index therefore no longer
consists of the translation source field alone, but of the translation source
field, the translation origin pointer field and the language field:

..  code-block:: sql

    KEY translation_source (l10n_source, l18n_parent, sys_language_uid)

The former single-column index remains the leftmost prefix of the new one, so
queries filtering on the translation source alone continue to use it.

In addition, the language related indexes are now generated for every
language-aware table, independent of whether the underlying columns were added
by the automatic TCA schema generation or declared in an :file:`ext_tables.sql`
file. Previously a table which declared one of those columns itself silently
ended up without them.

Impact
======

The database analyzer reports a changed index for every language-aware table
that defines `ctrl['translationSource']`, and an added index for tables that
declared their language related columns themselves. Both are non-destructive
schema changes and should be applied during the next update, either in the
:guilabel:`Admin Tools > Maintenance > Analyze Database Structure` module or
with :bash:`typo3 database:updateschema`.

Extensions which declared an equivalent index under a different name may end up
with a redundant index and can drop their own definition.

..  index:: Database, ext:core, NotScanned
