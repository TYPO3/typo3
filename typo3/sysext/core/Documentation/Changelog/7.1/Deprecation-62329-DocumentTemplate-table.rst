======================================================
Deprecation: #62329 - Deprecate DocumentTable::table()
======================================================

Description
===========

``DocumentTable::table()`` has been marked as deprecated.


Impact
======

Calling ``table()`` of the ``DocumentTable`` class will trigger a deprecation log message.


Affected installations
======================

Instances which use ``DocumentTable::table()`` for rendering tables.


Migration
=========

Use fluid for rendering instead.
