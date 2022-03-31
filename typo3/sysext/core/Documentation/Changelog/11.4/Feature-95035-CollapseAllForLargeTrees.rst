.. include:: /Includes.rst.txt

================================================
Feature: #95035 - "Collapse all" for large trees
================================================

See :issue:`95035`

Description
===========

A new button "Collapse all" is added for all SVG-based trees,
which is helpful for installations with a lot of pages or folders,
and editors can quickly get an overview again of the entry points.

The feature collapses all pages / folders inside the tree
except for the items on the root level.

This feature is now also available in all Record Selector and
Link Picker with SVG Trees as they all are based on the same
code base.


Impact
======

A new icon is shown in the Toolbar of all trees to select additional actions.

The "Refresh tree" button is now moved to the additional options
dropdown as well to clean up the Interface and enhance User Experience.

.. index:: Backend, JavaScript, ext:backend
