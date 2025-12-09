..  include:: /Includes.rst.txt

..  _feature-108431-1765302185:

====================================================================
Feature: #108431 - Add TCA datetime format=datetimesec option
====================================================================

See :issue:`108431`

Description
===========

The TCA configuration config option `type=datetime` can now specify
the `format=datetimesec` format to offer a date/time picker for entering
a date (*day, month, year*) with a specific time (*hour, minute, second*).

Previously, only a datepicker for *hour* and *minute* was available,
even though the utilized component (Flatpickr) supports entering seconds.

This format can either be specified for `dbType=datetime` (native SQL datetime
columns based on a timestamp that always includes seconds) or
for the `integer`-based storage without a `dbType` option (UNIX timestamp).

Example TCA configuration:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/tx_domain_model_myelement.php

    <?php

    return [
        // ...
        'columns' => [
            'meteor_impact' => [
                'label' => 'Time of estimated impact',
                'config' => [
                    'type' => 'datetime',
                    'format' => 'datetimesec',
                    'dbType' => 'datetime', // can also be omitted for integer-based storage
                    'nullable' => true, // can also be false
                ],
            ],
        ],
        // ...
    ];

..  hint::

    The format `datetimesec` is only allowed for:

    *   empty :php:`['config']['dbType']` (defaults to integer-based storage type)
    *   :php:`['config']['dbType'] = 'datetime'` (native date and time storage type)

    Other types (`date`, `time`, `timesec`) do not support both components
    and would yield unconsistent data.

Impact
======

Editors and integrators can now specify dates including seconds
in database record fields, if the fields are configured with the
new TCA config format `datetimesec`.

This can be set for any TCA field that already internally receives a
UNIX timestamp value.

*-- For the editor who has everything, but seconds.*

..  index:: Backend, NotScanned, ext:core
