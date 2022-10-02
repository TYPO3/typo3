.. include:: /Includes.rst.txt

.. _deprecation-97035:

=========================================================
Deprecation: #97035 - "required" option in "eval" keyword
=========================================================

See :issue:`97035`

Description
===========

Since :issue:`67354`, the FormEngine may use :php:`required` with a bool value
in a TCA field configuration, enabling the same functionality as the `required`
option within `eval`.

To clean up TCA and allow further refactoring, :php:`'eval' => 'required`
has been marked as deprecated.

Impact
======

Using `required` within `eval` in TCA and FlexForm will trigger an automatic
migration and therefore trigger a PHP :php:`E_USER_DEPRECATED` error.

Affected Installations
======================

All 3rd party extension either using :php:`'eval' => 'required'` or
`<eval>required</eval>` are affected.

Migration
=========

Migrate to :php:`'required' => true` and `<required>1</required>` to avoid
automatic migration and hence a deprecation log entry.

Example before migration:

..  code-block:: php

    'columns' => [
        'some_column' => [
            'title' => 'foo',
            'config' => [
                'eval' => 'trim,required',
            ],
        ],
    ],

Example after migration:

..  code-block:: php

    'columns' => [
        'some_column' => [
            'title' => 'foo',
            'config' => [
                'required' => true,
                'eval' => 'trim',
            ],
        ],
    ],

.. index:: TCA, NotScanned, ext:core
