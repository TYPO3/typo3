.. include:: /Includes.rst.txt

.. _feature-97035:

========================================================================
Feature: #97035 - Utilize "required" directly in TCA field configuration
========================================================================

See :issue:`97035`

Description
===========

Since :issue:`67354`, the FormEngine may use :php:`required` with a bool value
in a TCA field configuration, enabling the same functionality as the `required`
option within `eval`.

All TCA in TYPO3 Core is migrated to use the `required` configuration over the
corresponding `eval` option.

Impact
======

If not done already, TCA is automatically migrated to use :php:`'required' => true`
instead of the co-existing `eval` option. The automated migration will trigger
a deprecation entry though.

Example
=======

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

.. index:: Backend, TCA, ext:core
