====================================================
Deprecation: #62329 - Deprecate DocumentTable::table
====================================================

Description
===========

:php:`DocumentTable::table()` has been marked as deprecated.


Impact
======

Calling :php:`table()` of DocumentTable class will trigger a deprecation log message.


Affected installations
======================

Instances which use :php:`DocumentTable::table()` for rendering tables


Migration
=========

Use fluid for rendering instead.
