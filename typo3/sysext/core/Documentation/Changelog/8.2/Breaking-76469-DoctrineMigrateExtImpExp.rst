
.. include:: ../../Includes.txt

===============================================
Breaking: #76469 - Doctrine: migrate ext:ImpExp
===============================================

See :issue:`76469`

Description
===========

The return type of :php:`ImportExportController::exec_listQueryPid()`
has changed. Instead of returning either :php:`bool`, :php:`\mysqli_result`
or :php:`object` the return value always is a :php:`\Doctrine\DBAL\Driver\Statement`.


Impact
======

Using the mentioned method will not yield the expected result type.


Affected Installations
======================

All installations with a 3rd party extension using :php:`ImportExportController::exec_listQueryPid()`.


Migration
=========

Migrate all calls that work with the result of :php:`ImportExportController::exec_listQueryPid()`
to be able to handle :php:`\Doctrine\DBAL\Driver\Statement` objects.

.. index:: Database, PHP-API
