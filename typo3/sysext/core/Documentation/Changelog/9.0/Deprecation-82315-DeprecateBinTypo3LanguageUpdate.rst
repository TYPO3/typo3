.. include:: ../../Includes.txt

==============================================================
Deprecation: #82315 - Deprecate bin/typo3 lang:language:update
==============================================================

See :issue:`82315`

Description
===========

The command `lang:language:update` is an alias of `language:update`, therefore it's superfluous and
will be removed in the future.


Impact
======

The command `lang:language:update` will show a deprecation message when used.


Affected Installations
======================

All installations that make use of the command `lang:language:update`. Most likely there are cronjobs
that need to be adjusted.


Migration
=========

Use :shell:`bin/typo3 lang:language:update` instead.

.. index:: CLI, NotScanned, ext:lang
