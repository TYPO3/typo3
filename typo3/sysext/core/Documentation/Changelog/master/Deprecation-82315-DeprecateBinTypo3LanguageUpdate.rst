.. include:: ../../Includes.txt

=========================================================
Deprecation: #82315 - Deprecate bin/typo3 language:update
=========================================================

See :issue:`82315`

Description
===========

The command language:update is an alias of lang:language:update, therefore it's superfluous and will be removed in the future.


Impact
======

The command language:update will not work any more.


Affected Installations
======================

All installations that make use of the command language:update. Most likely there are cronjobs that need to be adjusted.


Migration
=========

Use bin/typo3 lang:language:update instead.

.. index:: CLI, NotScanned
