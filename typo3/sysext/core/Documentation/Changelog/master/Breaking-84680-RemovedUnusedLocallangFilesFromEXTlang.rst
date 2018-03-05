.. include:: ../../Includes.txt

===============================================================
Breaking: #84680 - Removed unused locallang files from EXT:lang
===============================================================

See :issue:`84680`

Description
===========

Removed the last unused locallang files from EXT:lang


Impact
======

Extensions or configuration that use one of the following locallang files will not show a translation anymore


Affected Installations
======================

All extensions or configuration that still uses one of the mentioned locallang files.


Migration
=========

Use your own language files.

.. index:: Backend, TCA, NotScanned