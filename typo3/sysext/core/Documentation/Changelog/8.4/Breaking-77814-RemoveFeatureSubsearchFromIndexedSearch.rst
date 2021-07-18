.. include:: ../../Includes.txt

===============================================================
Breaking: #77814 - Remove feature subsearch from indexed search
===============================================================

See :issue:`77814`

Description
===========

The feature subsearch which enabled the possibility to append previously searched words to the current
query was removed.

The option TypoScript :typoscript:`plugin.tx_indexedsearch.clearSearchBox` has been removed.


Impact
======

Frontend output of search results may change.

Setting the TypoScript option has no effect anymore.


Affected Installations
======================

Any installation using this option or using custom indexed search plugin templates.


Migration
=========

Remove the option in your custom TypoScript and remove the functionality in the Fluid plugin
template.

.. index:: Frontend, TypoScript, Fluid, ext:indexed_search
