..  include:: /Includes.rst.txt

..  _deprecation-105213-1728286135:

====================================
Deprecation: #105213 - TCA sub types
====================================

See :issue:`105213`

Description
===========

One of the main features of TCA are the record types. This allows to use
a single table for different purposes and in different contexts. The most
known examples of using record types are the "Page Types" of :sql:`pages`
and the "Content Types" of :sql:`tt_content`. For every specific type of
such table, it's possible to define the fields to be used and even
manipulate them e.g. change their label.

A special case since ever has been the plugin registration. This for
a long time has been done using the so called "sub types" feature of TCA.
This is another layer below record types and allows to further customize
the behaviour of a record type using another select field, defined via
:php:`subtype_value_field` as well as defining fields to be added
- :php:`subtypes_addlist` - or excluded - :php:`subtypes_excludelist` - for
the record type, depending on the selected sub type.

For a couple of version now, it's encouraged to register plugins just
as standard content elements via the :php:`tt_content` type field :php:`CType`.
Therefore, the special registration via the combination of the :php:`list`
record type and the selection of a sub type via the :php:`list_type` field
has already been deprecated with :ref:`deprecation-105076-1726923626`.

Since the "sub types" feature was mainly used for this scenario only, it has
now been deprecated as well. Registration of custom types should therefore
always be done by using record types. This makes configuration much cleaner
and more comprehensible.

Impact
======

Using :php:`subtype_value_field` in a TCA `types` configurations will
lead to a deprecation log entry containing information about where
adaptations need to take place.


Affected installations
======================

All installations using the sub types feature by defining a
:php:`subtype_value_field` in a TCA `types` configuration, which
is really uncommon as the feature was mainly used for plugin
registration in the :sql:`tt_content` table only.

Migration
=========

Replace any :php:`subtype_value_field` configuration with dedicated record
types. Please also consider migrating corresponding :php:`subtypes_addlist`
and :php:`subtypes_excludelist` definitions accordingly.

Before
^^^^^^

.. code-block:: php

    'ctrl' => [
        'type' => 'type',
    ],
    'columns' => [
        'type' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'A record type',
                        'value' => 'a_record_type'
                    ]
                ]
            ]
        ],
        'subtype' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'A sub type',
                        'value' => 'a_sub_type'
                    ]
                ]
            ]
        ],
    ],
    'types' => [
        'a_record_type' => [
            'showitem' => 'aField,bField',
            'subtype_value_field' => 'subtype',
            'subtypes_addlist' => [
                'a_sub_type' => 'pi_flexform'
            ],
            'subtypes_excludelist' => [
                'a_sub_type' => 'bField'
            ]
        ]
    ]


After
^^^^^

.. code-block:: php

    'ctrl' => [
        'type' => 'type',
    ],
    'columns' => [
        'type' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'A record type',
                        'value' => 'a_record_type'
                    ],
                    [
                        'label' => 'A sub type',
                        'value' => 'a_sub_type'
                    ]
                ]
            ]
        ],
    ],
    'types' => [
        'a_record_type' => [
            'showitem' => 'aField,bField'
        ],
        'a_sub_type' => [
            'showitem' => 'aField,pi_flexform'
        ]
    ]

..  index:: PHP-API, TCA, FullyScanned, ext:core
