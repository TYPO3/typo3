.. include:: /Includes.rst.txt

.. _important-100088-1677950866:

=========================================================
Important: #100088 - Remove dbType json for TCA type user
=========================================================

See :issue:`100088`

Description
===========

With :issue:`99226` the `dbType=json` option has been added for
TCA type `user`. After some reconsideration, it has been decided
to drop this option again in favor of the dedicated TCA type `json`.
Have a look to the according :ref:`changelog <feature-100088-1677965005>`
for further information.

Since the `dbType` option has not been released in any LTS version yet,
the option is dropped without further deprecation. Also no TCA migration
is applied.

In case you make already use of this `dbType` in your custom extension,
you need to migrate to the new TCA type.

Example:

..  code-block:: php

    // Before
    'myField' => [
        'config' => [
            'type' => 'user',
            'renderType' => 'myRenderType',
            'dbType' => 'json',
        ],
    ],

    // After
    'myField' => [
        'config' => [
            'type' => 'json',
            'renderType' => 'myRenderType',
        ],
    ],

.. index:: Backend, PHP-API, TCA, ext:backend
