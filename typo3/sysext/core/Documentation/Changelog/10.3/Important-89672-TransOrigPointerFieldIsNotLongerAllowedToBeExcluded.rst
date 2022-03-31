.. include:: /Includes.rst.txt

==============================================================================
Important: #89672 - transOrigPointerField is not longer allowed to be excluded
==============================================================================

See :issue:`89672`

Description
===========

The configured :php:`$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']`
can now not longer be excluded as this leads to inconsistent data stored in the
database. This happens when a non-admin user creates a localization by not having
the permission to edit the :php:`transOrigPointerField`. Usually this is the
:php:`l10n_parent` or :php:`l18n_parent` field.

A migration wizard is available that removes the option from your TCA and adds a
deprecation message to the deprecation log where code adaption has to take place.

.. index:: Backend, Database, TCA, ext:core
