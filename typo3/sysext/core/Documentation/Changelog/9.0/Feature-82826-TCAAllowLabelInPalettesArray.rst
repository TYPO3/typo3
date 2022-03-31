.. include:: /Includes.rst.txt

===================================================
Feature: #82826 - TCA Allow label in palettes array
===================================================

See :issue:`82826`

Description
===========

Displaying multiple fields in a :php:`palette` in TCA has this syntax:

.. code-block:: php

    'types' => [
        'myType' => [
            'showitem' => 'aField, --palette--;LLL:EXT:myExt/Resources/Private/Language/locallang.xlf:aPaletteDescription;aPalette, someOtherField',
        ],
    ],
    'palettes' => [
        'aPalette' => [
            'showitem' => 'aFieldInAPalette, anotherFieldInPalette',
        ],
    ],

Opening this record of type 'myType' displays single field 'aField', then a palette with the two fields 'aFieldInAPalette' and 'anotherFieldInPalette'
next to each other, and another single field 'anotherFieldInPalette'. The two field palette has a title 'LLL:EXT:myExt/Resources/Private/Language/locallang.xlf:aPaletteDescription'.

It is now allowed to declare the palette label within the 'palettes' array and leaving out the label within the palette usage:

.. code-block:: php

    'types' => [
        'myType' => [
            'showitem' => 'aField, --palette--;;aPalette, someOtherField',
        ],
    ],
    'palettes' => [
        'aPalette' => [
            'label' => 'LLL:EXT:myExt/Resources/Private/Language/locallang.xlf:aPaletteDescription',
            'showitem' => 'aFieldInAPalette, anotherFieldInPalette',
        ],
    ],

Declaring the label in a 'palettes' array can reduce boilerplate declarations if a palette is used over and over again in multiple types. If a label is
defined for a palette this way, it is always displayed. Setting a specific label in the 'types' array for a palette overrides the default label defined
within the 'palettes' array. There is no way to unset a label that is set within the 'palettes' array, it will always be displayed.


Impact
======

This feature is handy especially for integrators who create own content elements and re-use casual palettes like 'access' or 'headers' in tt_content
multiple times: Those palettes now have a default label defined and the label definition can be left out within the 'types' declaration of a content
element.

As a side effect, it may happen that the one or the other label is now additionally displayed on custom content elements by default.


.. index:: TCA, Backend
