
.. include:: /Includes.rst.txt

=====================================================
Breaking: #75645 - Doctrine: migrate ext:backend/Tree
=====================================================

See :issue:`75645`

Description
===========

This patch changes all database related functions to use the new Doctrine database API.
The method :php:`getDatabaseConnection()` has been removed.


Impact
======

Calls to the method :php:`AbstractTreeView::getDataInit()` will now return :php:`Statement` objects.
All other :php:`AbstractTreeView::getData*` methods now expect such a :php:`Statement` object
instead of a SQL resource.


Affected Installations
======================

All installations using TreeViews extending the AbstractTreeView.


Migration
=========

Migrate all calls that work with the result :php:`Statement` from TreeView to be able to
handle :php:`Statement` objects.

.. index:: Database, PHP-API, Backend
