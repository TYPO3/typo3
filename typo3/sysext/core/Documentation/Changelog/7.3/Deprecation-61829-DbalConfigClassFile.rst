
.. include:: /Includes.rst.txt

============================================================
Deprecation: #61829 - Deprecate config.classFile DBAL option
============================================================

See :issue:`61829`

Description
===========

The DBAL option `config.classFile` has been marked for deprecation,
and will be removed with TYPO3 CMS 8.


Impact
======

Using `config.classFile` option will throw a deprecation message.


Affected Installations
======================

Installations which use a user-defined DBAL database-handler.


Migration
=========

Load the class using the autoloader.


.. index:: PHP-API, ext:dbal
