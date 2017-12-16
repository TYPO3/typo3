
.. include:: ../../Includes.txt

===============================================================
Breaking: #68206 - Remove usage of typolist and typohead in RTE
===============================================================

See :issue:`68206`

Description
===========

The transformation for the tags `typolist` and `typohead` have been removed from the RTE.
The option and method `internalizeFontTags()` from RteHtmlParser have been removed.

Impact
======

The tags are not processed anymore by the RteHtmlParser.
Fonts are not internalized anymore.


Affected Installations
======================

All installations using the custom tags `typolist` and `typohead`.
All installations that use the method `internalizeFontTags()` will throw an fatal error.


Migration
=========

Substitute the tags by a tag `ul``or `header`.
Remove any usage of `internalizeFontTags()`
