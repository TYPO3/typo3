
.. include:: /Includes.rst.txt

=============================================================
Feature: #76108 - Replace ExtJS category tree with D3 and SVG
=============================================================

See :issue:`76108`

Description
===========

The Backend ExtJS category tree (renderType `selectTree`) has been replaced with one based on D3.js_ and SVG.
Tree implements a 'virtual scroll' pattern, meaning that it renders only as many nodes as fit in the viewport.

.. _D3.js: https://d3js.org/

Additionally the tree now display icon overlay (e.g. for disabled categories).

Structure
---------

There are three RequireJS modules:

- SvgTree.js - this is a base JS object able to render a SVG based tree. It can expand and collapse child nodes, render icons for each node, and keep track of the select nodes.
- SelectTree.js - extends the SvgTree object (prototype inheritance) with checkboxes
- SvgTreeToolbar.js - toolbar for SvgTree which allows to search, collapse all and expand all tree nodes

Visual Scroll
-------------

SvgTree renders only as many nodes as fit in the wrapping container. This requires that the wrapping container has a fixed height set.
So e.g. if one node takes 20px height, and the wrapper has 200px, only 10 nodes will be rendered at the time.

Data binding
------------

Thanks to D3, each SVG node representing tree item is bound to the data object. The general idea is that all operations (like showing/hiding/selecting...) are first performed on the dataset, and then the view (SVG) is refreshed.
In the :js:`initialize` function SvgTree loads the whole tree as json


Impact
======

- New tree is faster.
- A new 'indeterminate' state for the category has been introduced introduced. The category is in the  'indeterminate'  state if at last one of its descendants  is selected (checked).
- Tree data is not rendered inline in HTML any more but fetched via Ajax

.. index:: JavaScript, Backend
