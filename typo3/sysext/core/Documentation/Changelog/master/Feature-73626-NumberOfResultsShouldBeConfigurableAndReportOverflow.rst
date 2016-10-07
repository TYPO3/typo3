.. include:: ../../Includes.txt

============================================================================
Feature: #73626 - numberOfResults should be configurable and report overflow
============================================================================

See :issue:`73626`

Description
===========

Adds possibility to overwrite in TypoScript maximum number of Indexed Search results,
which previously was limited to 100.

TypoScript setting `plugin.tx_indexedsearch.settings.blind.numberOfResults` now became
a list of values. If number of results sent in request does not match any value from
the list, default (first) value will be used to keep DoS attack protection.

Values from `plugin.tx_indexedsearch.settings.blind.numberOfResults` are used as the
options in the select in advanced search mode. To keep backward compatibility default
values are 10, 25, 50 and 100.


Impact
======

TypoScript setting `plugin.tx_indexedsearch.settings.blind.numberOfResults` can be now
list of available number of results. Because of that it is possible to overwrite list
of values displayed in the advanced search mode. First value from the list will be used
as default.

.. index:: ext:indexed_search, TypoScript