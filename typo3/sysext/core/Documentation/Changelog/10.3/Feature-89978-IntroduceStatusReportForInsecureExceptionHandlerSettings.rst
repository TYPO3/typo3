.. include:: /Includes.rst.txt

=================================================================================
Feature: #89978 - Introduce Status Report for insecure exception handler settings
=================================================================================

See :issue:`89978`

Description
===========

When using a debug exception handler in production (either by configuring it explicitly
or by using the wrong application context) stack traces may disclose information.
To avoid such setups a new status report has been introduced that warns administrators if a debug exception handler is configured.


Impact
======

To mitigate the information disclosure, a new status report has
been introduced:

- if display errors is set to 1 (-> uses DebugExceptionHandler setting)
  and context is Production, an Error is displayed
- if display errors is set to 1 (-> uses DebugExceptionHandler setting)
  and context is Development, a Warning is displayed
- if the production exception handler setting is configured to use the
  DebugExceptionHandler, an Error is displayed

.. index:: Backend, LocalConfiguration, ext:reports
