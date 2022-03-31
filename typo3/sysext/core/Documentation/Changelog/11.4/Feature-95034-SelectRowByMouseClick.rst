.. include:: /Includes.rst.txt

============================================================
Feature: #95034 - List views: Select a row by clicking on it
============================================================

See :issue:`95034`

Description
===========

The multi record selection, introduced in :issue:`94906`, has been
improved for a convenience method, making the selection of rows
more pleasant. It's now possible to select a row by
simply clicking anywhere on it. Certainly, this does not influence
any other action on this row, e.g. a link or a button. Only if the
click event is on the row itself, e.g. any empty space, the automatic
selection is performed.

Besides selecting a single row, also the keyboard actions, introduced
in :issue:`94944`, can be used while clicking on the row, allowing to
further optimize workflows.

Impact
======

It's now possible to select a row by clicking anywhere on it.

.. index:: Backend, ext:backend
