.. include:: /Includes.rst.txt

.. _important-101535-1726059919:

=============================================================
Important: #101535 - Unused DB fields from tt_content removed
=============================================================

See :issue:`101535`

Description
===========

The database table `tt_content` contains all necessary fields for rendering
content elements.

Back with TYPO3 v4.7, a major feature to render certain Content Types in a more
accessible way, funded by the German Government (BLE_) with the
"Konjunkturpaket II" was merged into CSS Styled Content.

In this procedure, certain Content Types received new fields and rendering definitions, which
were stored in the database fields `accessibility_title`, `accessibility_bypass`
and `accessibility_bypass_text`.

When CSS Styled Content was removed in favor of Fluid Styled Content in TYPO3 v8, the DB
fields continued to exist in TYPO3 Core, so a migration from CSS Styled Content was possible.

However, the DB fields are not evaluated anymore since then, and are removed, along with
their TCA definition in `tt_content`.

If these fields are still relevant for a custom legacy installation, these DB fields need to be
re-created via TCA for further use in a third-party extension.

.. _BLE: https://typo3.org/article/typo3-receives-german-governmental-funding-for-accessibility-and-usability-project

.. index:: Database, PHP-API, ext:frontend
