.. include:: /Includes.rst.txt

.. _breaking-102971-1706453204:

====================================================================
Breaking: #102971 - Most classes of EXT:workspaces declared internal
====================================================================

See :issue:`102971`

Description
===========

A few additional classes of extension "workspaces" have been declared :php:`@internal`.
With this, most of the classes are now considered internal handling, except, of
course, dispatched events.


Impact
======

Extensions extending or using workspace classes as PHP API that are now marked
:php:`@internal` may break, when the Core changes such classes. This will not
be considered breaking.


Affected installations
======================

Few extensions extend workspaces as such, and the backend workspaces
application in particular.


Migration
=========

Extension authors who need to extend from classes within EXT:workspaces should
reconsider on why this needs to be done. They should expect these may break
without further notice.

Legit use cases can often be moved towards some additional event instead.
Extension authors are encouraged to come up with specific solutions in those cases.


.. index:: PHP-API, NotScanned, ext:workspaces
