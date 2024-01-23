.. include:: /Includes.rst.txt

.. _feature-102793-1704805673:

===================================================================================
Feature: #102793 - PSR-14 event for modifying default constraints in PageRepository
===================================================================================

See :issue:`102793`

Description
===========

The API class :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository` has a
method :php:`getDefaultConstraints()` which accumulates common restrictions for
a database query to limit a query for TCA-based tables in order to filter out
disabled, or scheduled records.

A new PSR-14 event
:php:`\TYPO3\CMS\Core\Domain\Event\ModifyDefaultConstraintsForDatabaseQueryEvent` has
been introduced, which allows to remove, alter or add constraints compiled by
TYPO3 for a specific table to further limit these constraints.

Impact
======

The new event contains a list of :php:`CompositeExpression` objects, allowing
to modify them via the :php:`getConstraints()` and
:php:`setConstraints(array $constraints)` methods.

Additional information, such as the used :php:`ExpressionBuilder` object or the
table name and the current :php:`Context` are also available within the event.

.. index:: PHP-API, ext:core
