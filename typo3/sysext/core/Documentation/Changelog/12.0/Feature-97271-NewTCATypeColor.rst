.. include:: /Includes.rst.txt

.. _feature-97271:

======================================
Feature: #97271 - New TCA type "color"
======================================

See :issue:`97271`

Description
===========

Especially TCA type :php:`input` has a wide range of use cases, depending
on the configured :php:`renderType` and the :php:`eval` options. Determination
of the semantic meaning is therefore usually quite hard and often leads to
duplicated checks and evaluations in custom extension code.

In our effort of introducing dedicated TCA types for all those use
cases, the TCA type :php:`color` has been introduced. It replaces the
:php:`renderType=colorpicker` of TCA type :php:`input`.

The TCA type :php:`color` features the following column configuration:

- :php:`behaviour`: :php:`allowLanguageSynchronization`
- :php:`default`
- :php:`fieldControl`
- :php:`fieldInformation`
- :php:`fieldWizard`
- :php:`mode`
- :php:`nullable`
- :php:`placeholder`
- :php:`readOnly`
- :php:`required`
- :php:`search`
- :php:`size`
- :php:`valuePicker`: :php:`items`

.. note::
   The value of TCA type :php:`color` columns is automatically trimmed before
   being stored in the database. Therefore, the :php:`eval=trim` option is no
   longer needed and should be removed from the TCA configuration.

.. note::
   The :php:`valuePicker` allows to define default color codes via :php:`items`
   for a TCA type :php:`color` field.

The following column configuration can be overwritten by page TSconfig:

- :typoscript:`readOnly`
- :typoscript:`size`

A complete migration from :php:`renderType=colorpicker` to :php:`type=color`
looks like the following:

..  code-block:: php

    // Before

    'a_color_field' => [
        'label' => 'Color field',
        'config' => [
            'type' => 'input',
            'renderType' => 'colorpicker',
            'required' => true,
            'size' => 20,
            'max' => 1024,
            'eval' => 'trim',
            'valuePicker' => [
                'items' => [
                    ['typo3 orange', '#FF8700'],
                ],
            ],
        ],
    ],

    // After

    'a_color_field' => [
        'label' => 'Color field',
        'config' => [
            'type' => 'color',
            'required' => true,
            'size' => 20,
            'valuePicker' => [
                'items' => [
                    ['typo3 orange', '#FF8700'],
                ],
            ],
        ]
    ]

An automatic TCA migration is performed on the fly, migrating all occurrences
to the new TCA type and triggering a PHP :php:`E_USER_DEPRECATED` error
where code adoption has to take place.

.. note::
    The corresponding FormEngine class has been renamed from
    :php:`InputColorPickerElement` to :php:`ColorElement`. An entry in
    the "ClassAliasMap" has been added for extensions calling this class
    directly, which is rather unlikely. The extension scanner will report
    any usage, which should then be migrated.

Impact
======

It's now possible to simplify the TCA configuration by using the new
dedicated TCA type :php:`color`.

.. index:: Backend, TCA, ext:backend
