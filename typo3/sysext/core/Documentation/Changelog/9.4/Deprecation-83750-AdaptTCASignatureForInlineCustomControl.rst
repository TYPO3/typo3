.. include:: ../../Includes.txt

============================================================
Deprecation: #83750 - Adapt TCA signature for customControls
============================================================

See :issue:`83750`

Description
===========

According to the TCA documentation since TYPO3 v4.7, the definition of "customControls" for "inline" columns
is as follows:

.. important::

    Numerical array containing definitions of custom header controls for IRRE fields. This makes it possible to
    create special controls by calling user-defined functions (userFuncs). Each item in the array item must be
    an array itself, with at least on key "userFunc" pointing to the user function to call.

The implementation instead relied on the userFunc string being provided as the key of the array.


Impact
======

TCA definition for "inline" fields using custom header controls for IRRE fields will trigger a PHP :php:`E_USER_DEPRECATED` error:

.. code-block:: php

    'some-column' => [
        'config' => [
            'type' => 'inline',
            // ...
            'customControls' => [
                \Vendor\MyExtension\Tca\MyFirstCustomControl::class . '->render',
                \Vendor\MyExtension\Tca\MySecondCustomControl::class . '->render'
            ]
        ]
    ]


Migration
=========

Update the TCA definition with a :php:`userFunc` key to specify the method to be called:

.. code-block:: php

    'some-column' => [
        'config' => [
            'type' => 'inline',
            // ...
            'customControls' => [
                [
                    'userFunc' => \Vendor\MyExtension\Tca\MyFirstCustomControl::class . '->render'
                ],
                [
                    'userFunc' => \Vendor\MyExtension\Tca\MySecondCustomControl::class . '->render'
                ]
            ]
        ]
    ]


.. index:: TCA, NotScanned, ext:core
