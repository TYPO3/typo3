===================================
Deprecation: #65111 - getDynTabMenu
===================================

Description
===========

The DocumentTemplate method ``getDynTabMenu()`` is deprecated.


Impact
======

The method was refactored and renamed. The new method ``getDynamicTabMenu()`` should be used.
The method ``getDynTabMenu()`` is now deprecated.


Affected installations
======================

All installations which make use of ``DocumentTemplate::getDynTabMenu()``


Migration
=========

Use ``DocumentTemplate::getDynamicTabMenu()`` instead of ``DocumentTemplate::getDynTabMenu()``
