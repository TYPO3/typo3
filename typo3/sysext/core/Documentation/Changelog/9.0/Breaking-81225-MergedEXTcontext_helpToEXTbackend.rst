.. include:: ../../Includes.txt

=========================================================
Breaking: #81225 - Merged EXT:context_help to EXT:backend
=========================================================

See :issue:`81225`

Description
===========

The extension `context_help` has been merged into the extension backend.


Impact
======

The extension backend can't be deactivated.
Any check if `context_help` is installed will return false.


Affected Installations
======================

Installations with extensions with checks for extension `context_help` being installed.


Migration
=========

Remove the checks.

.. index:: Backend, NotScanned
