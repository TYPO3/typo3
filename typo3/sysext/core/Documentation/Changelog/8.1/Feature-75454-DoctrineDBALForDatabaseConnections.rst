=========================================================================================
Feature: #75454 - Added PHP library "Doctrine DBAL" for Database Connections within TYPO3
=========================================================================================

Description
===========

The PHP library ``Doctrine DBAL`` has been added via composer dependency to work as a powerful database
abstraction layer with many features for database abstraction, schema introspection and
schema management within TYPO3.

A TYPO3-specific PHP class called ``TYPO3\CMS\Core\Database\ConnectionPool`` has been added as a
manager for database connections.

All connections configured below ``$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']`` are
accessible using this manager, enabling the parallel usage of multiple database systems.

By using the database abstraction options and the QueryBuilder provided SQL statements being
built will be properly quoted and compatible with different DBMS out of the box as far as
possible.

Existing ``$GLOBALS['TYPO3_CONF_VARS']['DB']`` options have been removed and/or migrated to the
new Doctrine-compliant options.

Documentation for Doctrine DBAL can be found at http://www.doctrine-project.org/projects/dbal.html.

The :php:``Connection`` class provides convenience methods for ``insert``, ``select``, ``update``,
``delete``and ``truncate`` statements. For ``select``, ``update`` an ``delete`` only simple
equality comparisons (``WHERE "aField" = 'aValue') are supported. For complex statements it's
required to use the :php:``QueryBuilder``.


Impact
======

Currently the :php:``DatabaseConnection`` class only uses Doctrine to establish the database
connection to MySQL, no advanced options are being used yet.

Connections always need to be requested with a table name so that the abstraction of
table names to database connections stays intact.

The :php:``ConnectionPool`` class can be used like this:

.. code-block:: php

   // Get a connection which can be used for muliple operations
   /** @var \TYPO3\CMS\Core\Database\Connecction $conn */
   $conn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('aTable');
   $affectedRows = $conn->insert(
      'aTable',
      $fields, // Associative array of column/value pairs, automatically quoted & escaped
   );

.. code-block:: php

   // Get a QueryBuilder, which should only be used a single time
   $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('aTable);
   $query->select('*')
      ->from('aTable)
      ->where(
         $query->expr()->eq('aField', $query->createNamedParameter($aValue)),
         $query->expr()->lte(
            'anotherField',
            $query->createNamedParameter($anotherValue)
         )
      );
   $rows = $query->execute()->fetchAll();

Extension authors are advised to use the ``ConnectionPool`` and ``Connection`` classes instead of using
the Doctrine DBAL directly in order to ensure a clear upgrade path when updates to the underlying
API need to be done.

.. index:: php
