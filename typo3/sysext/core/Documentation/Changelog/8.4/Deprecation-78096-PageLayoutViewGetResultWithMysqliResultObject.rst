.. include:: /Includes.rst.txt

=====================================================================================
Deprecation: #78096 - Deprecated PageLayoutView::getResult with mysqli_result objects
=====================================================================================

See :issue:`78096`

Description
===========

The method :php:`PageLayoutView::getResult` has been marked as deprecated with the usage of mysqli_result objects as first parameter.

Impact
======

Using the mentioned method with a mysqli_result object will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation using extensions which call :php:`PageLayoutView::getResult` with mysqli_result objects.


Migration
=========

The extension should migrate to use the Doctrine API for database queries.

.. index:: Backend, Database, PHP-API
