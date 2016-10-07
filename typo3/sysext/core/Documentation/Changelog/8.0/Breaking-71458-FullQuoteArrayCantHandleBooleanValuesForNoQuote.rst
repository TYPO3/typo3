
.. include:: ../../Includes.txt

==========================================================================
Breaking: #71458 - FullQuoteArray can't handle boolean values for $noQuote
==========================================================================

See :issue:`71458`

Description
===========

The API for `fullQuoteArray` allows the parameter `$noQuote` to be boolean but
converted it automatically to false as `$noQuote` is neither a string nor an
array. This behavior has been fixed, passing `true` for `$noQuote` now disables
quoting of any passed in values.


Impact
======

Passing in boolean `true` results in escaping being disabled for all values.


Affected Installations
======================

All installations making use of `INSERTmultipleRows()`, `INSERTquery()`,
`UPDATEquery()` or `fullQuoteArray()` and relying on the fact that quoting
remains enabled when `true` is passed as value for `$noQuote`.


Migration
=========

Pass the correct list of fields to disable quoting for unless none of the
fields should be quoted.

.. index:: PHP-API, Database
