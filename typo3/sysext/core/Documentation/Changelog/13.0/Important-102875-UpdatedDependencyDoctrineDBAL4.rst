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
directly need to adapt, see upgrade guides for Doctrine DBAL 4 on how
to migrate.

..  note::
    Doctrine DBAL 4.0 will be released slightly after the 13.0 and
    therefore it's raised to a development version for now. This will
    be changed to 4.0 after a release is out. As a intermediate solution
    it's required to use `composer require "doctrine/dbal":"4.0.x-dev@dev"`
    to add it or lower the minimum-stability in the root `composer.json`.

See `Doctrine DBAL 4.x Upgrade Guide <https://github.com/doctrine/dbal/blob/4.0.x/UPGRADE.md>`_
and `Doctrine DBAL 4.x Upgrade Guide <https://github.com/doctrine/dbal/blob/3.8.x/UPGRADE.md>`_
for further information about Doctrine DBAL API changes and how to mitigate them.

.. index:: Database, PHP-API, ext:core
