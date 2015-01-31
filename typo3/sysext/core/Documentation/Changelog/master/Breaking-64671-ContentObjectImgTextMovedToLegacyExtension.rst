===========================================================================
Breaking: #64671 - Outdated ContentObject IMGTEXT moved to legacy extension
===========================================================================

Description
===========

The TypoScript Content Objects IMGTEXT is moved to the legacy extension "compatibility6".

Impact
======

Any TypoScript using the cObject directly or via CSS Styled Content configured via renderMethod=table will result
in an empty output in the TYPO3 Frontend.


Affected installations
======================

TYPO3 CMS 7 installations still using the cObject need the compatibility6 extension loaded.

Migration
=========

Any installation should migrate to alternatives such as CSS Styled Content without a table-based rendering for
 text w/ image elements to customize the output of content.
