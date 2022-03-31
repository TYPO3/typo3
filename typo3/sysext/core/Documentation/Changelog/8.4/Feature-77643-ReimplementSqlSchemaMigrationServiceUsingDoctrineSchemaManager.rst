.. include:: /Includes.rst.txt

====================================================================================
Feature: #77643 - Reimplement SqlSchemaMigrationService using Doctrine SchemaManager
====================================================================================

See :issue:`77643`

Description
===========

The SqlSchemaMigrationService has been reimplemented using a LL(*) Parser for CREATE TABLE
statements. The new parser supports MySQL syntax for CREATE TABLE statements. Based on the
abstract syntax tree produced by this parser Doctrine Table objects are created that
implement a DBMS independent representation of the schema and are used with the Doctrine
SchemaManager to handle the schema migrations needs of the TYPO3 core.


Impact
======

Update suggestions from the new SchemaMigrator are per connection, on all additional
connections only explicitly mapped tables are managed. MySQL specific data types are being
mapped to the closest matching standard type, for example TINYINT to SMALLINT. The support
for foreign keys has been enhanced as a result of the additional capabilities of the
Doctrine SchemaManager.

.. index:: Database, PHP-API
