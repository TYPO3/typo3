.. include:: /Includes.rst.txt

.. _deprecation-99739-1674869090:

======================================================
Deprecation: #99739 - Indexed array keys for TCA items
======================================================

See :issue:`99739`

Description
===========

Using indexed array keys for the :php:`items` configuration of TCA types
:php:`select`, :php:`radio` and :php:`check` is now deprecated.

Impact
======

Using indexed array keys for the :php:`items` configuration array items of TCA
types :php:`select`, :php:`radio` and :php:`check` will trigger a deprecation
level log entry. A TCA migration is in place.

Affected installations
======================

All installations having custom extensions that make use of TCA types
:php:`select`, :php:`radio` or :php:`check` and define at least one entry in
the :php:`items` array.

itemsProcFunc
_____________

The :php:`items` array handed over to custom :php:`itemsProcFunc` functions
contains the new object type :php:`TYPO3\CMS\Core\Schema\Struct\SelectionItem`
which acts as a compatibility layer for old style indexed keys. Accessing,
writing and reading items still work in the old way. Added items will be
automatically converted. For third-party extensions supporting both TYPO3 v11
(or lower) and v12 it is recommended to keep using indexed keys.

Migration
=========

To migrate your TCA, change all indexed keys according to the following mapping
table:

+--------+-------------+
| Before | After       |
+--------+-------------+
| 0      | label       |
+--------+-------------+
| 1      | value       |
+--------+-------------+
| 2      | icon        |
+--------+-------------+
| 3      | group       |
+--------+-------------+
| 4      | description |
+--------+-------------+

Examples:

..  code-block:: php

    // Before
    'select' => [
        'label' => 'My select field',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                [
                    'Selection 1',
                    '1',
                    'my-icon-identifier',
                    'default',
                ],
                [
                    0 => 'Selection 2',
                    1 => '2',
                ],
            ],
        ],
    ],

    // After
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

    // Before
    'select_checkbox' => [
        'label' => 'My select checkbox field',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectCheckBox',
            'items' => [
                [
                    'My select checkbox field',
                    '1',
                    'my-icon-identifier',
                    'default',
                    'My custom description',
                ],
                [
                    0 => 'My select checkbox field',
                    1 => 'value' => '2',
                ],
            ],
        ],
    ],

    // After
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

    // Before
    'radio' => [
        'label => 'My radio field',
        'config' => [
            'type' => 'radio',
            'items' => [
                [
                    'Radio 1',
                    '1',
                ],
                [
                    0 => 'Radio 2',
                    1 => '2',
                ],
            ],
        ],
    ],

    // After
    'radio' => [
        'label => 'My radio field',
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

    // Before
    'check' => [
        'config' => [
            'type' => 'check',
            'items' => [
                ['Click on me'],
            ],
        ],
    ],

    // After
    'check' => [
        'config' => [
            'type' => 'check',
            'items' => [
                ['label' => 'Click on me'],
            ],
        ],
    ],

    // Before
    'check' => [
        'config' => [
            'type' => 'check',
            'items' => [
                [
                    'invertStateDisplay' => true,
                    0 => 'Click on me',
                ],
            ],
        ],
    ],

    // After
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

Before:

..  code-block:: xml

    <select_single_1>
        <label>select_single_1 description</label>
        <description>field description</description>
        <config>
            <type>select</type>
            <renderType>selectSingle</renderType>
            <items>
                <numIndex index="0">
                    <numIndex index="0">foo1</numIndex>
                    <numIndex index="1">foo1</numIndex>
                </numIndex>
                <numIndex index="1">
                    <numIndex index="0">foo2</numIndex>
                    <numIndex index="1">foo2</numIndex>
                </numIndex>
            </items>
        </config>
    </select_single_1>

After:

..  code-block:: xml

    <select_single_1>
        <label>select_single_1 description</label>
        <description>field description</description>
        <config>
            <type>select</type>
            <renderType>selectSingle</renderType>
            <items>
                <numIndex index="0">
                    <label>foo1</label>
                    <value>foo1</value>
                </numIndex>
                <numIndex index="1">
                    <label>foo2</label>
                    <value>foo2</value>
                </numIndex>
            </items>
        </config>
    </select_single_1>

.. index:: TCA, FullyScanned, ext:backend
