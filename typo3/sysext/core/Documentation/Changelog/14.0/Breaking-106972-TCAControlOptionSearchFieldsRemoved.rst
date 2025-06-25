..  include:: /Includes.rst.txt

..  _breaking-106972-1750856858:

===========================================================
Breaking: #106972 - TCA control option searchFields removed
===========================================================

See :issue:`106972`

Description
===========

The TCA control option :php:`searchFields` has been removed.

Based on the Schema API and the :php:`SearchableSchemaFieldsCollector` component,
the handling of fields to be included in searches has been changed. By default
all fields of suitable field types, such as `input` or `text` are automatically
considered.

To manually configure searchable fields, the new :php:`searchable` field
configuration can be set in a field's TCA configuration. See the full list
:ref:`here <feature-106972-1750856721>`.
Unsupported field types (such as `file`, `inline`, etc.) are not
considered searchable and do not support the :php:`searchable` option.

..  note::

    Be aware that this does not apply to the `PageTSConfig` option used by
    `EXT:linkvalidator`: `mod.linkvalidator.searchFields`.

Impact
======

The option is no longer evaluated. It is automatically removed at runtime
through a TCA migration, and a deprecation log entry is generated to highlight
where adjustments are required.

In case :ref:`suitable <feature-106972-1750856721>` fields are found, which are
not in the to be removed :php:`searchFields` option, those are set to
:php:`searchable => false` to keep previous behaviour.


Affected installations
======================

All installations using this option in their TCA configuration.


Migration
=========

Remove the :php:`searchFields` option from your TCA `ctrl` section.

If needed, use the :php:`searchable` option in individual field definitions
to control which fields are included in search functionality.

..  index:: TCA, FullyScanned, ext:core
