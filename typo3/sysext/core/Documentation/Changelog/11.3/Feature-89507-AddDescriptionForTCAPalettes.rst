.. include:: /Includes.rst.txt

==================================================
Feature: #89507 - Add description for TCA palettes
==================================================

See :issue:`89507`

Description
===========

A new TCA property :php:`description` on palettes entry level has been
introduced. If provided, the FormEngine will render its value below the
palette label, similar to the TCA field description. The value data
type is the same as for the palette label: a localized string. This
additional help text can therefore be used to clarify some field
usages directly in the UI.

.. note::

   In contrast to the palette label, the description property can not
   be overwritten on a record type basis.

Example usage:

.. code-block:: php

   'types' => [
       '0' => [
           'showitem' => '
               --div--;palette,
                   --palette--;;palette_1,
           '
       ]
   ],

   'palettes' => [
       'palette_1' => [
           'label' => 'palette_1',
           'description' => 'palette_1_description',
           'showitem' => 'palette_field_1, palette_field_2, palette_field_3',
       ],
   ],


Impact
======

Integrators now have the ability to add additional information
to TCA palettes, supporting editors on their daily work.

.. index:: Backend, TCA, ext:backend
