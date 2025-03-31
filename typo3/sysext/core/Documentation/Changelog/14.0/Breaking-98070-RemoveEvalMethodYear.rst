..  include:: /Includes.rst.txt

..  _breaking-98070-1743452794:

==========================================
Breaking: #98070 - Remove eval method year
==========================================

See :issue:`98070`

Description
===========

The eval method `year` has been used to check the value of a TCA field. Its
implementation has never been completed and just casted the value to an integer.

As there is no definition what value a year can be, it has been removed
without substitution.


Impact
======

The value `year` has been removed from the eval list.

The TCA migration will trigger a deprecation log entry when
building the final TCA.


Affected installations
======================

TYPO3 instances using old extensions which provide custom TCA configurations
having this option set.


Migration
=========

Remove the setting from the TCA and use a TCA type which suits better
to your needs.

.. code-block:: php

    // Use type "number" with optional range restriction
    'variant_a' => [
        'label' => 'My year',
        'config' => [
            'type' => 'number',
            'range' => [
                'lower' => 1990,
                'upper' => 2038,
            ],
            'default' => 0,
        ],
    ],

    // Use a date field with optional range restriction
    'variant_b' => [
        'label' => 'My year',
        'config' => [
            'type' => 'datetime',
            'range' => [
                'lower' => gmmktime(0, 0, 0, 1, 1, 1990),
                'upper' => gmmktime(23, 59, 59, 12, 31, 2038),
            ],
            'nullable' => true,
        ],
    ],

..  index:: Backend, TCA, NotScanned, ext:backend
