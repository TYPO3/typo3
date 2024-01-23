.. include:: /Includes.rst.txt

.. _feature-95808-1702313827:

========================================================
Feature: #95808 - Enable item groups from foreign tables
========================================================

See :issue:`95808`

Description
===========

A new TCA option :php:`foreign_table_item_group` has been introduced for the TCA
types `select` and `category`. It allows extension authors to define a
specific field in the foreign table, holding an item group identifier.
As described in the :ref:`TCA reference <t3tca:columns-select-properties-items>`,
this needs to be a :php:`string`.

Therefore, it's now possible to also use the item groups feature, introduced
with :issue:`91008`, for TCA columns with a foreign table lookup.

Example
=======

..  code-block:: php

    'select_field' => [
        'label' => 'select_field',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                ['label' => 'static item 1', 'value' => 'static-1', 'group' => 'group1'],
            ],
            'itemGroups' => [
                'group1' => 'Group 1 with items',
                'group2' => 'Group 2 from foreign table',
            ],
            'foreign_table' => 'tx_extension_foreign_table',
            'foreign_table_item_group' => 'itemgroup'
        ],
    ],

In case the :php:`foreign_table_item_group` field of a foreign record
contains an item group identifier, not set in the local :php:`itemGroups`
configuration, the database value will be used as label in the select box,
as it's also the case for static items with a :php:`group` set to a value,
which is not configured in :php:`itemGroups`.

Impact
======

Using the new :php:`foreign_table_item_group` TCA config option, it's now
possible to use the items group feature even for items from foreign tables.

.. index:: Backend, TCA, ext:backend
