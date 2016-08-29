===================================================
Deprecation: #77557 - Method QueryView->tableWrap()
===================================================

Description
===========

Method :php:``QueryView->tableWrap()`` has been marked as deprecated.


Impact
======

Extensions using this method will trigger a deprecation log entry.


Affected Installations
======================

Extensions using :php:``QueryView->tableWrap()``


Migration
=========

Use :php:``'<pre>' . $str . '</pre>'`` instead.