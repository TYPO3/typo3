.. include:: /Includes.rst.txt

.. _breaking-102108-1696618684:

=====================================================
Breaking: #102108 - TCA `[types][bitmask_*]` settings
=====================================================

See :issue:`102108`

Description
===========

Handling of two settings has been removed from the
TYPO3 Core codebase:

* :php:`$GLOBALS['TCA']['someTable']['types']['bitmask_excludelist_bits']`
* :php:`$GLOBALS['TCA']['someTable']['types']['bitmask_value_field']`


Impact
======

These two fields allowed to set record "sub types" based on a record
bitmask field, typically :php:`'type' => 'check'` or :php:`'type' => 'radio'`.

This has been removed, the settings are not considered anymore when
rendering records in the backend record editing interface.


Affected installations
======================

Both settings have been used very rarely: Neither Core nor published TER extensions
revealed a single usage. The extension scanner will find affected extensions.


Migration
=========

In case extensions still use these two rather obscure settings, they should
switch to casual :php:`$GLOBALS['TCA']['someTable']['ctrl']['type']` fields instead,
which can be powered by columns based on string values.

Note the overall "subtype" record logic of TCA is within an ongoing process to
be removed in TYPO3 v13, so the basic thinking should be: There is a record, and its
details can be configured using :php:`$GLOBALS['TCA']['someTable']['ctrl']['type']`,
and that's it. Extensions using "sub types" or this bitmask detail need to simplify
and eventually deliver according upgrade wizards to adapt existing records.


.. index:: TCA, FullyScanned, ext:backend
