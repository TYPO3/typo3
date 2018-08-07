.. include:: ../../Includes.txt

===============================================
Breaking: #77379 - Doctrine: Typo3DbQueryParser
===============================================

See :issue:`77379`

Description
===========

While migrating the database endpoint for the persistence functionality of Extbase to Doctrine DBAL, the `Typo3DbQueryParser` class
has been completely rewritten to work on a `QueryBuilder` object instead of plain arrays and strings. The PHP method
:php:`Typo3DbQueryParser->parseQuery()` has been removed, instead the new equivalent
:php:`Typo3DbQueryParser->convertQueryToDoctrineQueryBuilder()` has been introduced.

Additionally, the PHP method :php:`Typo3DBBackend->injectQueryParser()` has been removed, as the `Typo3DbQueryParser` class is not a
singleton instance anymore but always rebuilt when needed.


Impact
======

Calling one of the methods above will result in a fatal PHP error.


Affected Installations
======================

TYPO3 instances with custom Extbase database backend and parsing functionality.


Migration
=========

Switch to Doctrine DBAL and :php:`Typo3DbQueryParser->convertQueryToDoctrineQueryBuilder()` which results in the same behaviour.

.. index:: PHP-API, Database
