=====================================================================
Breaking: #64696 - Content Element "search" moved to legacy extension
=====================================================================

Description
===========

The TYPO3-inhouse "search" functionality, which is based on FORM and SEARCHRESULTS ContentObjects and the content
element itself (CType=search) has been moved to the legacy extension "compatibility6".

Impact
======

Content elements of the Type "search" are missing and not rendered in the frontend anymore
unless the extension compatibility6 is installed. TypoScript using SEARCHRESULTS directly will return nothing.


Affected installations
======================

Any installation using the simple "search" Content Element or the SEARCHRESULTS Content Object directly will break.

Migration
=========

For TYPO3 CMS 7, installing the compatibility6 extension brings back the existing functionality. For the long term
the affected installations should be migrate to a better suited solution for searching.
