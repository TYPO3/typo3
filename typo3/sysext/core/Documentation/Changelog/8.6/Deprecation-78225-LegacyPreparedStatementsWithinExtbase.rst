.. include:: ../../Includes.txt

==============================================================
Deprecation: #78225 - Legacy PreparedStatements within Extbase
==============================================================

See :issue:`78225`

Description
===========

Extbase has a way to set raw statements with PreparedStatements based on the legacy DatabaseConnection a.k.a. :code:`TYPO3_DB`.
This functionality has been marked as deprecated.


Impact
======

Calling a query within Extbase with :php:`$query->setStatement($preparedStatement)` using a
:php:`\TYPO3\CMS\Core\Database\PreparedStatement` object will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 extension with Extbase functionality using custom prepared statements with the legacy database API.


Migration
=========

Use the same method :php:`setStatement()` and provide a QueryBuilder object or a Statement object based on Doctrine DBAL.

.. index:: Database, PHP-API, ext:extbase
