.. include:: ../../Includes.txt

=============================================================================
Feature: #88805 - Add type to TYPO3\CMS\Core\Database\Query\QueryBuilder::set
=============================================================================

See :issue:`88805`

Description
===========

Using :php:`TYPO3\CMS\Core\Database\Query\QueryBuilder::set()` will accept as fourth parameter a type that the query value should be casted to, if third parameter (:php:`createNamedParameter`) is set to true.
The default value will be a string type.


Impact
======

Type safe query parameter setting is now also possible via :php:`set()`.

Example:: php

   $queryBuilder->set($fieldName, $fieldValue, true, \PDO::PARAM_INT);

will make sure $fieldValue is handled as a int type in the resulting DB query.

.. index:: Database, ext:core