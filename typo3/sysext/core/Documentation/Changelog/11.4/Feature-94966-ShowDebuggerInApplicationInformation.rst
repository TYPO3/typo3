.. include:: /Includes.rst.txt

==========================================================
Feature: #94966 - Show debugger in Application Information
==========================================================

See :issue:`94966`

Description
===========

The "Application Information" menu is now able to show an enabled debugger and
its version, if available. Supported debuggers are xdebug and Zend Debugger at
the moment.


Impact
======

If a debugger is activated and can be determined via :php:`extension_loaded()`,
the "Application Information" will show such an activated debugger.

.. index:: Backend, ext:backend
