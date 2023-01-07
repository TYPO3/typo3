..  include:: /Includes.rst.txt

..  _migration:

=========
Migration
=========

From TYPO3 version 11 to version 12
===================================

The `chart.js`_ library, used to render charts in a dashboard widget, has been
updated from version 2.9 to version 4, introducing some breaking changes. For
TYPO3 v12 there is a migration layer in place to migrate known and used affected
settings. If a migration is executed, an entry is written to the deprecation
log.

The CSS file :file:`EXT:dashboard/Resources/Public/Css/Contrib/chart.css` became
obsolete with the update and has therefore been removed without replacement.

Migrate the chart.js configuration as mentioned in the table below:

================================   ============================
Old setting                        New setting
================================   ============================
graphConfig/options/scales/xAxes   graphConfig/options/scales/x
graphConfig/options/scales/yAxes   graphConfig/options/scales/y
================================   ============================

Please also consult the migration guides available at

*   https://www.chartjs.org/docs/latest/migration/v3-migration.html
*   https://www.chartjs.org/docs/latest/migration/v4-migration.html

.. _chart.js: https://www.chartjs.org/
