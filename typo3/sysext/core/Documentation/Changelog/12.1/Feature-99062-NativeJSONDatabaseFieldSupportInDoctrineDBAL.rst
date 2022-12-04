.. include:: /Includes.rst.txt

.. _feature-99062-1668170141:

=====================================================================
Feature: #99062 - Native JSON database field support in Doctrine DBAL
=====================================================================

See :issue:`99062`

Description
===========

TYPO3 Core's Database API based on Doctrine DBAL now supports the native
database field type `json`, which is already available for all supported DBMS
of TYPO3 v12.

JSON-like objects or arrays are automatically serialized during writing a
dataset to the database, when the native JSON type was used in the database
schema definition.


Impact
======

By using the native database field declaration `json` in e.g. :file:`ext_tables.sql`
files within an extension, TYPO3 now converts arrays or objects of type
:php:`\JsonSerializable` into a serialized JSON value in the database when
persisting such values via :php:`Connection->insert()` or
:php:`Connection->update()`, if no explicit database types are handed in as additional
method argument.

TYPO3 now utilizes the native type mapping of Doctrine to convert special types,
such as JSON database field types automatically for writing.

Example :file:`ext_tables.sql`:

..  code-block:: sql

    CREATE TABLE tx_myextension_domain_model_book (
        title varchar(200) DEFAULT '',
        contents json
    );

..  note::

    However, when reading a record from the database via QueryBuilder, it is
    still necessary to transfer the serialized value to an array or object,
    performing a custom serialization for the time being.


.. index:: Database, ext:core
