.. include:: /Includes.rst.txt

.. _feature-99739-1674867455:

======================================================
Feature: #99739 - Associative array keys for TCA items
======================================================

See :issue:`99739`

Description
===========

It is now possible to define associative array keys for the :php:`items`
configuration of TCA types :php:`select`, :php:`radio` and :php:`check`. The
new keys are called: :php:`label`, :php:`value`, :php:`icon`, :php:`group` and
:php:`description`.

Examples:

..  code-block:: php

    'columns' => [
        'select' => [
            'label' => 'My select field',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'Selection 1',
                        'value' => '1',
                        'icon' => 'my-icon-identifier',
                        'group' => 'default',
                    ],
                    [
                        'label' => 'Selection 2',
                        'value' => '2',
                    ],
                ],
            ],
        ],
        'select_checkbox' => [
            'label' => 'My select checkbox field',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'items' => [
                    [
                        'label' => 'My select checkbox field',
                        'value' => '1',
                        'icon' => 'my-icon-identifier',
                        'group' => 'default',
                        'description' => 'My custom description',
                    ],
                    [
                        'label' => 'My select checkbox field',
                        'value' => '2',
                    ],
                ],
            ],
        ],
        'radio' => [
            'label' => 'My radio field',
            'config' => [
                'type' => 'radio',
                'items' => [
                    [
                        'label' => 'Radio 1',
                        'value' => '1',
                    ],
                    [
                        'label' => 'Radio 2',
                        'value' => '2',
                    ],
                ],
            ],
        ],
        'check' => [
            'config' => [
                'type' => 'check',
                'items' => [
                    [
                        'invertStateDisplay' => true,
                        'label' => 'Click on me',
                    ],
                ],
            ],
        ],
    ],

Impact
======

It is now much easier and clearer to define the TCA :php:`items` configuration
with associative array keys. The struggle to remember which option is first,
label or value, is now over. In addition, optional keys like :php:`icon` and
:php:`group` can be omitted, for example, when one desires to set the
:php:`description` option.

.. index:: TCA, ext:backend
