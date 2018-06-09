.. include:: ../../Includes.txt

====================================================
Feature: #84749 - Hide "duplicate" button by default
====================================================

See :issue:`84749`

Description
===========

The "duplicate" button visibility can now be managed with userTsConfig using:

- :ts:`options.showDuplicate = 1`
- :ts:`options.showDuplicate.[table] = 1`


Impact
======

The button was only introduced in 9.0, but would with this change be hidden again.

.. index:: Backend, TSConfig, ext:backend