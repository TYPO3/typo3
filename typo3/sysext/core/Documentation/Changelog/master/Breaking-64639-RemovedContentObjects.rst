====================================================================
Breaking: #63687 - Outdated ContentObjects moved to legacy extension
====================================================================

Description
===========

The TypoScript Content Objects (cObjects) CLEARGIF, COLUMNS, CTABLE, OTABLE and HRULER are moved to the legacy extension
EXT:compatibility6.

Impact
======

Any TypoScript using the cObjects above will result in an empty output in the TYPO3 Frontend.


Affected installations
======================

TYPO3 CMS 7 installations still using the cObjects need the compatibility6 extension loaded if the rendering should
be continued.

Migration
=========

Any installation should migrate to alternatives such as FLUIDTEMPLATE to customize the output of the content.
