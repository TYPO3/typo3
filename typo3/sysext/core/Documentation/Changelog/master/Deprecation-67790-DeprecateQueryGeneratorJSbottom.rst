==========================================================
Deprecation: #67790 - Deprecate QueryGenerator::JSbottom()
==========================================================

Description
===========

The method ``QueryGenerator::JSbottom()``, which was used to append JavaScript code, has been marked for deprecation.


Impact
======

All calls to the PHP method will throw a deprecation warning.


Affected Installations
======================

Instances which make use of ``QueryGenerator::JSbottom()``.


Migration
=========

No migration, use requireJS module and register the module through pageRenderer.
