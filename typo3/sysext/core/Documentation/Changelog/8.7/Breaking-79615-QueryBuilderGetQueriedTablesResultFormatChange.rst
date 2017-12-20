.. include:: ../../Includes.txt

=====================================================================
Breaking: #79615 - QueryBuilder getQueriedTables result format change
=====================================================================

See :issue:`79615`

Description
===========

:php:`QueryBuilder::getQueriedTables` return value has been changed from array with key being table name and value being
table alias (or null) to array with a key being table alias and value being table name. Both keys and the value should
be filled.
This allows to return multiple entries for the same table (in case inner join is made).

Example for the :sql:`tt_content` table inner joined with self and joined with :sql:`sys_language`:

.. code-block:: php

    [
      'tt_content_alias' => 'tt_content',
      'tt_content' => 'tt_content',
      'sys_language' => 'sys_language'
    ]

Previously the array (for the same case) looked like:

.. code-block:: php

    [
      'tt_content' => 'tt_content_alias',
      'sys_language' => null
    ]


Impact
======

All code which rely on the result format of the :php:`getQueriedTables` method needs to be adapted.
The first parameter of the :php:`QueryRestrictionInterface::buildExpression` (:php:`$queriedTables`)
expects a new array structure.


Affected Installations
======================

All installations with custom implementation of Query Restriction (classes implementing :php:`QueryRestrictionInterface`).
All installations where table array passed to :php:`buildExpression` method is created manually (without using :php:`QueryBuilder::getQueriedTables`).


Migration
=========

The code of the :php:`buildExpression` method in custom :php:`QueryRestrictionInterface`
implementations needs to adapted to be able to handle the new incoming array structure.
Format of the first parameter passed to :php:`buildExpression` needs to be adapted in case
a query restriction is used directly (without using :php:`QueryBuilder::getQueriedTables`).

.. index:: PHP-API, Database
