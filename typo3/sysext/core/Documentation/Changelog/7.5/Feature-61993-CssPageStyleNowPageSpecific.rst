
.. include:: ../../Includes.txt

===========================================================================
Feature: #61993 - _CSS_PAGE_STYLE is now only included on the affected page
===========================================================================

See :issue:`61993`

Description
===========

CSS set via the TypoScript property `_CSS_PAGE_STYLE` was concatenated and
compressed with the non-page-specific CSS and therefore loaded on pages it did
not affect at all.

Impact
======

The behaviour from now on is that `_CSS_PAGE_STYLE` is included only on the
affected page. Depending on your configuration it will be written in an external
file and included on the page or directly added as inline CSS block. Compression
for page specific CSS also depends on the global `config.compressCss` setting.


.. index:: TypoScript, Frontend
