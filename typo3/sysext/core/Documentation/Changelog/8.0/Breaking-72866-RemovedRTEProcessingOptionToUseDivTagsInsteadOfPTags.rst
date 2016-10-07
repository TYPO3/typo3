
.. include:: ../../Includes.txt

==================================================================================
Breaking: #72866 - Removed RTE processing option to use div tags instead of p tags
==================================================================================

See :issue:`72866`

Description
===========

The Rich Text Editor TSconfig processing instructions `RTE.default.proc.useDIVasParagraphTagForRTE` and
`RTE.default.proc.remapParagraphTag` have been removed.

The Rich Text Editor is now always getting HTML content wrapped with <p> tags instead of the optional <div> tags.


Impact
======

Using any of the options above will have no effect anymore.


Affected Installations
======================

TYPO3 instances with custom Rich Text Editors (EXT:rtehtmlarea is not affected).

.. index:: TSConfig, Frontend, Backend, RTE
