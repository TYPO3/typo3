.. include:: ../../Includes.txt

=============================================================
Breaking: #80876 - Remove system extension css_styled_content
=============================================================

See :issue:`80876`

Description
===========

The long-time used system extension CSS Styled Content has been removed from the TYPO3 Core.


Impact
======

Rendering sites with TypoScript based on CSS Styled Content or referencing any TypoScript, resources or
PHP classes from this extension may result in fatal PHP errors or empty frontend output.

Extensions depending on the frontend rendering based on CSS Styled Content directly will work
unpredictably.


Affected Installations
======================

Installations that run their frontend based on CSS Styled Content as TypoScript, and extensions
depending on CSS Styled Content rendering instead of default rendering.


Migration
=========

The system extension "Fluid Styled Content" (EXT:fluid_styled_content) which was introduced in TYPO3 v7, 
acts as a drop-in replacement for CSS Styled Content since TYPO3 v8.

Install fluid styled content (if not happened yet) and prepare the Fluid templates to show the frontend
rendering accordingly to the previous output.

For managing content in the TYPO3 Backend and can be used transparently when migrating from CSS Styled
Content to Fluid Styled Content.

.. index:: Fluid, Frontend, PHP-API, TypoScript, NotScanned, ext:css_styled_content