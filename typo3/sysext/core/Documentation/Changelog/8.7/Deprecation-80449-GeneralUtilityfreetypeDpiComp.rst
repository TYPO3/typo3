.. include:: /Includes.rst.txt

=====================================================
Deprecation: #80449 - GeneralUtility::freetypeDpiComp
=====================================================

See :issue:`80449`

Description
===========

The method :php:`GeneralUtility::freetypeDpiComp` has been marked as deprecated.


Impact
======

Calling this method will trigger a deprecation log entry.


Affected Installations
======================

Any installation using custom GraphicalFunctions where GDlib/Freetype does custom calculations.


Migration
=========

No substitution available.

.. index:: PHP-API
