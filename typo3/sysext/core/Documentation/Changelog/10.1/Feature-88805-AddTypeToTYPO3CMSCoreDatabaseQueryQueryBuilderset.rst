.. include:: /Includes.rst.txt

====================================================================================
Feature: #88805 - Add type to \\TYPO3\\CMS\\Core\\Database\\Query\\QueryBuilder::set
====================================================================================

See :issue:`88805`

Description
===========

:php:`TYPO3\CMS\Core\Database\Query\QueryBuilder::set()` accepts as additional fourth parameter
a type the query value should be casted to when third parameter (:php:`createNamedParameter`)
is :php:`true`. Per default string (:php:`\PDO::PARAM_STR`) is used.

Impact
======

Type safe query parameter setting is now also possible via :php:`set()`.

Example:

.. code-block:: php

   $queryBuilder->set($fieldName, $fieldValue, true, \PDO::PARAM_INT);

ensures :php:`$fieldValue` is handled as integer type in the resulting database query.

.. index:: Database, ext:core
