
.. include:: ../../Includes.txt

===========================================================================
Breaking: #64671 - Outdated ContentObject IMGTEXT moved to legacy extension
===========================================================================

See :issue:`64671`

Description
===========

The TypoScript Content Object IMGTEXT has been moved to the legacy extension "compatibility6".

Impact
======

Any TypoScript using the cObject directly or via CSS Styled Content configured using `renderMethod=table` will result
in an empty output in the TYPO3 Frontend.


Affected installations
======================

TYPO3 CMS 7 installations still using the cObject need EXT:compatibility6 to be loaded.

Migration
=========

Any installation should migrate to alternatives such as CSS Styled Content without a table-based rendering for
text w/ image elements to customize the output of content.


.. index:: Backend, Frontend, TypoScript
