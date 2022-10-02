.. include:: /Includes.rst.txt

.. _deprecation-97109:

=================================================
Deprecation: #97109 - TCA type none "cols" option
=================================================

See :issue:`97109`

Description
===========

The TCA type `none` had two option keys for the same functionality: `cols` and
`size`. In order to simplify the available configuration, `cols` has been
dropped in favour of `size`.

Impact
======

Defining the option `cols` for the TCA type `none` will trigger a PHP :php:`E_USER_DEPRECATED` error.
An automatic migration is in place, which will be displayed in the TCA
Migrations view of the Upgrade module.

Affected Installations
======================

All installations using the `cols` option for the TCA type `none`.

Migration
=========

Rename the option `cols` to `size`.

Before:

..  code-block:: php

    'columns' => [
        'aColumn' => [
            'config' => [
                'type' => 'none',
                'cols' => 20,
            ],
        ],
    ],

After:

..  code-block:: php

    'columns' => [
        'aColumn' => [
            'config' => [
                'type' => 'none',
                'size' => 20,
            ],
        ],
    ],

.. index:: TCA, NotScanned, ext:backend
