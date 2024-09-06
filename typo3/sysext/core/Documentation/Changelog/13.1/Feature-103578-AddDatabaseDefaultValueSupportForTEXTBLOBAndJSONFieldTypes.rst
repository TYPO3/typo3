.. include:: /Includes.rst.txt

.. _feature-103578-1712678936:

=========================================================================================
Feature: #103578 - Add database default value support for TEXT, BLOB and JSON field types
=========================================================================================

See :issue:`103578`

Description
===========

Database default values for :sql:`TEXT`, :sql:`JSON` and :sql:`BLOB` fields
could not be used in a cross-database, vendor-compatible manner, for
example in :file:`ext_tables.sql`, or as default database scheme generation
for TCA-managed tables and types.

Direct default values are still unsupported, but since
`MySQL 8.0.13+ <https://dev.mysql.com/doc/relnotes/mysql/8.0/en/news-8-0-13.html#mysqld-8-0-13-data-types>`__
this is possible by using default value expressions, albeit in a slightly
differing syntax.

Example
-------

..  code-block:: sql
    :caption: EXT:my_extension/ext_tables.sql

    CREATE TABLE `tx_myextension_domain_model_entity` (
      `some_field` TEXT NOT NULL DEFAULT 'default-text',
      `json_field` JSON NOT NULL DEFAULT '{}'
    );

..  code-block:: php
    :caption: Insert a new record using the defined default values

    $connection = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getConnectionByName(ConnectionPool::DEFAULT_NAME);
    $connection->insert(
        'tx_myextension_domain_model_entity',
        [
            'pid' => 123,
        ]
    );

Advanced example with value quoting
-----------------------------------

..  code-block:: sql
    :caption: EXT:my_extension/ext_tables.sql

    CREATE TABLE a_textfield_test_table
    (
        # JSON object default value containing single quote in json field
        field1 JSON NOT NULL DEFAULT '{"key1": "value1", "key2": 123, "key3": "value with a '' single quote"}',

        # JSON object default value containing double-quote in json field
        field2 JSON NOT NULL DEFAULT '{"key1": "value1", "key2": 123, "key3": "value with a \" double quote"}',
    );

Impact
======

Database :sql:`INSERT` queries that do not provide values for fields with
defined default values, and that do not use TCA-powered TYPO3
APIs, can now be used, and will receive default values defined at databaselevel.
This also accounts for dedicated applications operating directly
on the database table.

..  note::

    TCA-unaware API will not consider different TCA or FormEngine default
    value overrides and settings. So it's good to provide the basic default
    both in TCA and at database level, if added manually.

.. index:: Database, ext:core
