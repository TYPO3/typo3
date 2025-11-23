..  include:: /Includes.rst.txt

..  _breaking-98070-1743452794:

==========================================
Breaking: #98070 - Remove eval method year
==========================================

See :issue:`98070`

Description
===========

The eval method `year` was used to validate the value of a TCA field. Its
implementation was never completed and simply cast the value to an integer.

As there is no clear definition of what a valid year value should be, the
method has been removed without substitution.

Impact
======

The value `year` has been removed from the list of supported `eval` options.

The TCA migration will trigger a deprecation log entry when building the final
TCA.

Affected installations
======================

TYPO3 installations using old extensions that define custom TCA configurations
with this option set are affected.

Migration
=========

Remove the `year` eval setting from your TCA configuration and use a TCA field
type that better suits your needs.

..  code-block:: php

    // Use type "number" with an optional range restriction
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

    // Use a date field with an optional range restriction
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
