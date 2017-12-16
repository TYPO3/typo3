
.. include:: ../../Includes.txt

=====================================================
Breaking: #68276 - Remove ExtJS Quicktips if possible
=====================================================

See :issue:`68276`

Description
===========

The method `PageRenderer::enableExtJSQuickTips()`, which was used to enable ExtJS quicktips, has been removed.
In some places like RTE or workspaces the Quicktips are still in use, but will be removed as soon as possible.


Impact
======

All calls to the PHP method `PageRenderer::enableExtJSQuickTips()` will throw a fatal error.


Affected Installations
======================

Instances which make use of `PageRenderer::enableExtJSQuickTips()`.


Migration
=========

No migration, use bootstrap tooltips, which work out of the box as alternative.
Simple add `data-toggle="tooltip"` and `data-title="your tooltip"` to any element you want.

Example
-------

.. code-block:: html

	<a href="#" data-toggle="tooltip" data-title="My very nice title">My Link</a>
