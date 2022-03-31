
.. include:: /Includes.rst.txt

===================================================================
Breaking: #72783 - Removed RTE transformation option preserveTables
===================================================================

See :issue:`72783`

Description
===========

The RTE transformation option `preserveTables` that allowed keeping HTML table
tags and their contents has been removed.

Additionally, the methods `RteHtmlParser->removeTables` and `HtmlParser->getAllParts` have been removed
without substitution.


Impact
======

When the RTE is configured to use `overruleMode = ts` instead of the default "ts_css" the
option `RTE.default.proc.preserveTables = 1` will have no effect anymore.

Calling `RteHtmlParser->removeTables` or `HtmlParser->getAllParts` inside a custom extension will result in a fatal PHP error.


Affected Installations
======================

Any TYPO3 instance with a legacy-mode (overruleMode = ts) from TYPO3 3.x or an extension doing custom transformations
by using `RteHtmlParser->removeTables`.


Migration
=========

Use the overruleMode `ts_css` instead which keeps the tables as they are. If tables should be disallowed inside the RTE
the option `RTE.default.proc.denyTags := addToList(table)` can be used instead.

.. index:: PHP-API, RTE, TSConfig
