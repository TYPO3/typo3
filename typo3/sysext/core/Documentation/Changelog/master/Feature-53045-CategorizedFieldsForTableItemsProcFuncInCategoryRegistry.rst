.. include:: ../../Includes.txt

================================================================================
Feature: #53045 - Categorized fields for table itemsProcFunc in CategoryRegistry
================================================================================

See :issue:`53045`

Description
===========

A new method :php:`getCategoryFieldItems()` is added to the :php:`\TYPO3\CMS\Core\Category\CategoryRegistry` class.

This method can be used as an `itemsProcFunc` in TCA and returns a list of all categorized fields of a table.

The table for which the categorized fields should be returned can be specified in two ways.

Static table
------------

You can provide a static table name in the config of your TCA field:

.. code-block:: php

    'itemsProcFunc' => \TYPO3\CMS\Core\Category\CategoryRegistry::class . '->getCategoryFieldItems',
    'categoryFieldsTable' => 'my_table_name',


Dynamic table selection
-----------------------

You can also provide a list of tables. The active table can be selected by using a display condition:

.. code-block:: php

    'itemsProcFunc' => \TYPO3\CMS\Core\Category\CategoryRegistry::class . '->getCategoryFieldItems',
    'categoryFieldsTable' => [
        'categorized_pages' => [
            'table' => 'pages',
            'activeCondition' => 'FIELD:menu_type:=:categorized_pages'
        ],
        'categorized_content' => [
            'table' => 'tt_content',
            'activeCondition' => 'FIELD:menu_type:=:categorized_content'
        ]
    ]


Impact
======

The method :php:`getCategoryFieldsForTable()` is removed. It could only handle the `tt_content` menus
`categorized_pages` and `categorized_content`.

A new method  :php:`getCategoryFieldItems()` is added that can be used by third party code for any
categorized table.

.. index:: Backend, PHP-API, TCA
