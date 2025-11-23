..  include:: /Includes.rst.txt

..  _breaking-106972-1750856858:

===========================================================
Breaking: #106972 - TCA control option searchFields removed
===========================================================

See :issue:`106972`

Description
===========

The TCA control option :php:`$GLOBALS['TCA'][$table]['ctrl']['searchFields']`
has been removed.

With the introduction of the Schema API and the
:php:`\TYPO3\CMS\Core\Schema\SearchableSchemaFieldsCollector` component,
the handling of fields included in searches has changed. By default, all
fields of suitable types, such as `input` or `text`, are now automatically
considered searchable.

To manually define searchable fields, use the new :php:`searchable` field
configuration option within a field's TCA configuration.
See the full list of supported field types
:ref:`here <feature-106972-1750856721>`.
Unsupported field types (such as `file`, `inline`, etc.) are not considered
searchable and do not support the `searchable` option.

..  note::

    This change does not affect the Page TSconfig option used by
    EXT:linkvalidator: :tsconfig:`mod.linkvalidator.searchFields`.

Impact
======

The `searchFields` option is no longer evaluated. It is automatically
removed at runtime through a TCA migration, and a deprecation log entry is
generated to highlight where adjustments are required.

If :ref:`suitable <feature-106972-1750856721>` fields are detected that were
not listed in the removed :php:`searchFields` option, they are automatically
set to :php:`searchable => false` to preserve previous behavior.

Affected installations
======================

All installations that define `searchFields` in their TCA configuration.

Migration
=========

Remove the `searchFields` option from the `ctrl` section of your TCA
configuration.

If needed, use the `searchable` option in individual field definitions
to control which fields are included in the search functionality.

..  index:: TCA, FullyScanned, ext:core
