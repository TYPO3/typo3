.. include:: /Includes.rst.txt

.. _deprecation-96444:

=====================================================================
Deprecation: #96444 - authMode select items keywords moved to index 5
=====================================================================

See :issue:`96444`

Description
===========

With the introduction of itemGroups, the array index 3 of the select items array
has been shifted one position up. Before that, the index 3 was used for
descriptions and index 4 for an optional keyword `EXPL_ALLOW` or `EXPL_DENY`.
These are used together with :php:`'authMode' => 'individual'` to explicitly
allow or deny single items.

Since descriptions now occupy the array index 4, the former usage of this index
is now shifted as well one position up to index 5.

Impact
======

For backwards compatibility reasons, a TCA migration is in place, which will
check for these special keywords and move them one index up. This will log a
"TCA migration done" message in the admin tools upgrade module.

Affected Installations
======================

All installations, which use TCA type `select` with `authMode=individual`, while
defining the keywords `EXPL_ALLOW` or `EXPL_DENY` in the items array at index 4.

Migration
=========

Before:

..  code-block:: php
    :emphasize-lines: 12

    'columns' => [
        'aColumn' => [
            'config' => [
                'type' => 'select',
                'authMode' => 'individual',
                'items' => [
                    [
                        0 => 'Label 1',
                        1 => 'Value 1',
                        2 => null,
                        3 => null,
                        4 => 'EXPL_ALLOW',
                    ],
                ],
            ],
        ],
    ],

After:

..  code-block:: php
    :emphasize-lines: 12,13

    'columns' => [
        'aColumn' => [
            'config' => [
                'type' => 'select',
                'authMode' => 'individual',
                'items' => [
                    [
                        0 => 'Label 1',
                        1 => 'Value 1',
                        2 => null,
                        3 => null,
                        4 => '', // This can be left empty
                        5 => 'EXPL_ALLOW',
                    ],
                ],
            ],
        ],
    ],

.. index:: Backend, TCA, NotScanned, ext:backend
