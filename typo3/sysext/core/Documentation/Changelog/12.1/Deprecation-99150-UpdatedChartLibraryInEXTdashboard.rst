.. include:: /Includes.rst.txt

.. _deprecation-99150-1669026092:

============================================================
Deprecation: #99150 - Updated chart library in EXT:dashboard
============================================================

See :issue:`99150`

Description
===========

The library `chart.js` used to render charts in a dashboard has been
updated to version 4.x, introducing some breaking changes. A migration layer is
in place to migrate known and used affected settings.


Impact
======

The CSS file :file:`EXT:dashboard/Resources/Public/Css/Contrib/chart.css` became
obsolete with the update of `chart.js` and was therefore removed without
replacement.

If a migration is executed, an entry will be written into the deprecation log.

Affected installations
======================

All plugins providing third-party chart widgets are affected.


Migration
=========

Migrate the configuration as mentioned in the table below.

================================   ============================
Old setting                        New setting
================================   ============================
graphConfig/options/scales/xAxes   graphConfig/options/scales/x
graphConfig/options/scales/yAxes   graphConfig/options/scales/y
================================   ============================

Also, please consult the migration guides available at

* https://www.chartjs.org/docs/latest/migration/v3-migration.html
* https://www.chartjs.org/docs/latest/migration/v4-migration.html

.. index:: Backend, JavaScript, NotScanned, ext:dashboard
