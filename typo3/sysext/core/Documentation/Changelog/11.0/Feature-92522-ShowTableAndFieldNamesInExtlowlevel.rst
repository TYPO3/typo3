.. include:: /Includes.rst.txt

============================================================
Feature: #92522 - Show table and field names in ext:lowlevel
============================================================

See :issue:`92522`

Description
===========

If the configuration `['BE']['debug']` is enabled and the current user is an
administrator, the name of a DB table or DB field is appended to the select
options in the "Full Search" module of ext:lowlevel.


Impact
======

This simplifies working with and debugging problems inside the full search of
ext:lowlevel, as developers usually know the DB table and field names better
than their labels configured in TCA.

.. index:: Backend, ext:lowlevel
