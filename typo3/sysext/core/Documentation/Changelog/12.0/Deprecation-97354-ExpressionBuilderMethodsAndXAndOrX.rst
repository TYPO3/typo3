.. include:: /Includes.rst.txt

.. _deprecation-97354:

================================================================
Deprecation: #97354 - ExpressionBuilder methods andX() and orX()
================================================================

See :issue:`97354`

Description
===========

`doctrine/dbal` `deprecated`_  the :php:`ExpressionBuilder` methods
:php:`andX()` and :php:`orX`. Therefore, those methods have also been
deprecated in the Core facade class (:php:`\TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder`),
to avoid shifting too far away.

.. _`deprecated`: https://github.com/doctrine/dbal/commit/84328cd947706210caebcaea3ca0394b3ebc4673

Impact
======

Using :php:`ExpressionBuilder->andX()` and :php:`ExpressionBuilder->orX()`
will trigger a PHP :php:`E_USER_DEPRECATED` error when called.

Affected Installations
======================

All installations, using the deprecated methods :php:`ExpressionBuilder->andX()`
and :php:`ExpressionBuilder->orX()` in custom extension code. The extension
scanner will detect any usage as weak match.

Migration
=========

Extensions should use the corresponding replacement:

- :php:`ExpressionBuilder->andX()` -> :php:`ExpressionBuilder->and()`
- :php:`ExpressionBuilder->orX()` -> :php:`ExpressionBuilder->or()`

.. note::

   The replacement methods have already been added in a forward-compatible way
   in TYPO3 v11. Thus giving extension developers the ability to adopt new
   methods and still being able to support multiple Core versions without
   workarounds.

For example, the following select query:

..  code-block:: php

    $rows = $queryBuilder
        ->select(...)
        ->from(...)
        ->where(
            $queryBuilder->expr()->andX(...),   // replace with and(...)
            $queryBuilder->expr()->orX(...)     // replace with or(...)
        )
        ->executeQuery()
        ->fetchAllAssociative();

should be replaced with:

..  code-block:: php

    $rows = $queryBuilder
        ->select(...)
        ->from(...)
        ->where(
            $queryBuilder->expr()->and(...), // replacement for andX(...)
            $queryBuilder->expr()->or(...)   // replacement for orX(...)
        )
        ->executeQuery()
        ->fetchAllAssociative();

.. index:: Database, FullyScanned, ext:core
