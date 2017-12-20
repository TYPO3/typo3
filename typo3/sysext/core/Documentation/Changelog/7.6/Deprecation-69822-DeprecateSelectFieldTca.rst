
.. include:: ../../Includes.txt

=============================================================
Deprecation: #69822 - Deprecate TCA settings of select fields
=============================================================

See :issue:`69822`

Description
===========

Using the TCA field type `select` without specifying a valid `renderType` has been marked as deprecated.

Additionally the usage of `renderMode` for select fields has been marked as deprecated.

These `renderType` settings are available:


.. container:: table-row

   Key
         renderType

   Datatype
         string

   Description
        This setting specifies how the select field should be displayed. Available options are:

        - `selectSingle` - Normal select field for selecting a single value.
        - `selectSingleBox` - Normal select field for selecting multiple values.
        - `selectCheckBox` - List of checkboxes for selecting muliple values.
        - `selectMultipleSideBySide` - Two select fields, items can be selected from the right
          field, selected items are displayed in the left select.
        - `selectTree` - A tree for selecting hierarchical data.

   Scope
        Display

.. note::

    If a field has no `renderType` set but `maxitems` is set, the migration will set
    `renderType` to `selectSingle` in case of `maxitems` is <= 1 otherwise `renderType`
    is set to `selectMultipleSideBySide`


Impact
======

The old TCA settings can still be used. A migration handles the update of the settings.


Affected Installations
======================

All installations with extensions that configure TCA select fields in the old format.


Migration
=========

Extension authors need to add the correct `renderType` setting to their select
field definitions.


.. index:: TCA, Backend
