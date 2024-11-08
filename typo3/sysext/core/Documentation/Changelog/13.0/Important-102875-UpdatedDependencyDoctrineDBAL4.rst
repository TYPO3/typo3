.. include:: /Includes.rst.txt

.. _important-102875-1705915392:

========================================================
Important: #102875 - Updated Dependency: Doctrine DBAL 4
========================================================

See :issue:`102875`

Description
===========

TYPO3 v13 ships with Doctrine DBAL with at least version 4.0.

TYPO3 extends some Doctrine DBAL classes, enriching behaviour and provide
these as public API surface, for example :php:`Connection`, :php:`QueryBuilder`
and the :php:`ExpressionBuilder` and are most likely used by extensions. Minor
signature changes and removed methods are not mitigated and passed as breaking
changes.

Custom extensions using low-level Doctrine DBAL API and functionality
directly need to adapt, consult the upgrade guides for Doctrine DBAL on how
to migrate.

See `Doctrine DBAL 4.0 Upgrade Guide <https://github.com/doctrine/dbal/blob/4.0.0/UPGRADE.md>`__
and `Doctrine DBAL 3.8 Upgrade Guide <https://github.com/doctrine/dbal/blob/3.8.0/UPGRADE.md>`__
for further information about Doctrine DBAL API changes and how to mitigate them.

.. index:: Database, PHP-API, ext:core
