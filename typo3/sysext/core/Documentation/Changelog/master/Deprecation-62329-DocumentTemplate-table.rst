====================================================
Deprecation: #62329 - Deprecate DocumentTable::table
====================================================

Description
===========

:code:`DocumentTable::table()` has been marked as deprecated.


Impact
======

Calling :code:`table()` of DocumentTable class will trigger a deprecation log message.


Affected installations
======================

Instances which use :code:`DocumentTable::table()` for rendering tables


Migration
=========

Use fluid for rendering instead.
