.. include:: /Includes.rst.txt

========================================================
Feature: #80619 - Extend Link Generation within TypoLink
========================================================

See :issue:`80619`

Description
===========

Generating a link to a page, email, url, email in the TYPO3 Frontend is usually handled via the
so-called ``typolink`` functionality. Generating links is now flexible, extensions can register
their own link-building functionality via :php:`$GLOBALS[TYPO3_CONF_VARS][FE][typolinkBuilder][$linkType]`
in the extensions ``ext_localconf.php``.

All existing functionality for TypoLink via TypoScript etc. still works as before.


Impact
======

The TYPO3 Core itself handles all native link types (email, url, page, record, file, folder) via
this functionality already, and it can be overridden.

The functionality goes hand-in-hand with the LinkService registration functionality for setting
links of a specific type.

.. index:: Frontend, PHP-API, LocalConfiguration
