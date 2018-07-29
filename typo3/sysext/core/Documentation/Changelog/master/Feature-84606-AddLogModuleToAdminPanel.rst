.. include:: ../../Includes.txt

==============================================
Feature: #84606 - Add Log Module to AdminPanel
==============================================

See :issue:`84606`

Description
===========

A log module has been added to the adminPanel to display log entries generated during the current request.

It displays all log entries generated via the logging framework during the request.

Display options include grouping by log level and component, additionally the log level
which shall be logged has been made configurable.


Impact
======

A new AdminPanel sub module displaying log entries has been added in a new main module "Debug" which may
be extended with further debug information.

.. index:: Frontend, ext:adminpanel
