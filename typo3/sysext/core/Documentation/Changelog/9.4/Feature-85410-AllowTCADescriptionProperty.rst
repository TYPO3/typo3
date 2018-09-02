.. include:: ../../Includes.txt

================================================
Feature: #85410 - Allow TCA description property
================================================

See :issue:`85410`

Description
===========

The new `TCA` property `description` on column field level has been introduced.
The value data type is a localized string, similar and on the same level as `label`.

The property can be used to display an additional help text between the field label and
the user input when editing records. As an example, the core uses the description property
in the site configuration module when editing a site on some properties like `identifier`.

The property is available on all common `TCA` types like `input` and `select` and so on.

Example::

    'columns' => [
        'myField' => [
            'label' => 'My label',
            'description' => 'LLL:EXT:my_ext/Resources/Private/Language/locallang_tca.xlf:field.description',
            'config' => [
                'type' => 'input',
            ],
        ],
    ],


Impact
======

The change is fully backwards compatible and can be used by integrators or administrators
to hint editors for expected field input.

.. index:: Backend, FlexForm, TCA
