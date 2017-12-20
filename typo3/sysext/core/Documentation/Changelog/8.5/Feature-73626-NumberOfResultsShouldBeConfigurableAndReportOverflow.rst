.. include:: ../../Includes.txt

============================================================================
Feature: #73626 - numberOfResults should be configurable and report overflow
============================================================================

See :issue:`73626`

Description
===========

Adds possibility to overwrite the maximum number of Indexed Search results with TypoScript
which previously was limited to 100.

The TypoScript setting `plugin.tx_indexedsearch.settings.blind.numberOfResults` now became
a list of values. If the number of results sent in the request does not match any value of
this list, the first value will be used to protect against DoS attacks.

Values from `plugin.tx_indexedsearch.settings.blind.numberOfResults` are used as the
options in the selectbox in advanced search mode. To keep backward compatibility the default
values are 10, 25, 50 and 100.


Impact
======

The TypoScript setting `plugin.tx_indexedsearch.settings.blind.numberOfResults` can be now
a list of available number of results. Because of that it is possible to overwrite the list
of values displayed in the advanced search mode. The first value from the list will be used
as default.

.. index:: ext:indexed_search, TypoScript, Frontend