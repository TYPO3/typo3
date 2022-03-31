.. include:: /Includes.rst.txt

=========================================================================
Deprecation: #79341 - TCA richtext configuration in defaultExtras dropped
=========================================================================

See :issue:`79341`

Description
===========

Enabling richtext rendering for fields in the Backend record editor has been simplified.

In the past, a typical :php:`TCA` configuration of a richtext field looked like:

.. code-block:: php

    'columns' => [
        'content' => [
            'config' => [
                'type' => 'text',
            ],
            'defaultExtras' => 'richtext:rte_transform',
        ],
    ];

The :php:`defaultExtras` is obsolete and substituted with :php:`enableRichtext` within the :php:`config` section:

.. code-block:: php

    'columns' => [
        'content' => [
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
            ],
        ],
    ];


If the RTE was enabled for a specific type only, it looked like this:

.. code-block:: php

    'columns' => [
        'content' => [
            'config' => [
                'type' => 'text',
            ],
        ],
    ],
    'types' => [
        'myType' => [
            'columnsOverrides' => [
                'aField' => [
                    'defaultExtras' => 'richtext:rte_transform',
                ],
            ],
        ],
    ],

This is now:

.. code-block:: php

    'columns' => [
        'content' => [
            'config' => [
                'type' => 'text',
            ],
        ],
    ],
    'types' => [
        'myType' => [
            'columnsOverrides' => [
                'aField' => [
                    'config' => [
                        'enableRichtext' => true,
                    ],
                ],
            ],
        ],
    ],


Impact
======

Using defaultExtras to enable richtext editor will stop working in TYPO3 v9. An automatic :php:`TCA` migration
transfers to the new syntax in TYPO3 v8 and logs deprecations.


Affected Installations
======================

All installations using :php:`defaultExtras` for richtext configuration.


Migration
=========

Remove the defaultExtras line and set :php:`'enableRichtext' => true,` within the config section of the field.
This is allowed in :php:`columnsOverrides` for specific record types, too.

.. index:: Backend, FlexForm, RTE, TCA
