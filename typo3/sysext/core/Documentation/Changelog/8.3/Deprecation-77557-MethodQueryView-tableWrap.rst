
.. include:: /Includes.rst.txt

.. _deprecation-77557:

===================================================
Deprecation: #77557 - Method QueryView->tableWrap()
===================================================

See :issue:`77557`

Description
===========

Method :php:`QueryView->tableWrap()` has been marked as deprecated.


Impact
======

Extensions using this method will trigger a deprecation log entry.


Affected Installations
======================

Extensions using :php:`QueryView->tableWrap()`


Migration
=========

Use :php:`'<pre>' . $str . '</pre>'` instead.

.. index:: PHP-API, Backend
