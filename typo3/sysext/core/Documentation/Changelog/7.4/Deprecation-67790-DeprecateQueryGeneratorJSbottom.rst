
.. include:: ../../Includes.txt

==========================================================
Deprecation: #67790 - Deprecate QueryGenerator::JSbottom()
==========================================================

See :issue:`67790`

Description
===========

The method `QueryGenerator::JSbottom()` which was used to append JavaScript code has been marked as deprecated.


Impact
======

All calls to the PHP method will throw a deprecation warning.


Affected Installations
======================

Instances which make use of `QueryGenerator::JSbottom()`.


Migration
=========

No migration, use requireJS modules and register the module through `pageRenderer`.


.. index:: PHP-API, Backend, JavaScript
