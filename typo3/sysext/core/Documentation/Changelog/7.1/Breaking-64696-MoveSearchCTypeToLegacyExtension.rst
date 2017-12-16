
.. include:: ../../Includes.txt

=====================================================================
Breaking: #64696 - Content Element "search" moved to legacy extension
=====================================================================

See :issue:`64696`

Description
===========

The TYPO3 default `search` functionality, which was based on the `FORM` and `SEARCHRESULTS` ContentObjects and the
content element CType=search itself has been moved to the legacy extension EXT:compatibility6.


Impact
======

Content elements of the Type "Search" are gone and will no longer be rendered in the frontend
unless EXT:compatibility6 is loaded. TypoScript using `SEARCHRESULTS` directly will return nothing.


Affected installations
======================

Any installation using the simple "search" content element or the `SEARCHRESULTS` ContentObject directly will break.


Migration
=========

For TYPO3 CMS 7, installing EXT:compatibility6 brings back the existing functionality. For the long term
the affected installations should be migrate to a better suited solution for searching.
