.. include:: ../../Includes.txt

==============================================================
Feature: #84517 - Recordlist - Make csv delimiter configurable
==============================================================

See :issue:`84517`

Description
===========

Two new PageTSconfig options were added for the DatabaseRecordList:

- `mod.web_list.csvDelimiter = ,` - defines the delimiter between csv values
- `mod.web_list.csvQuote = "` - defines the quote-character to wrap csv values


Impact
======

It is now possible to control the delimiter and quote-character of the recordlist csv export.

.. index:: Backend