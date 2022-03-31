
.. include:: /Includes.rst.txt

================================================================
Breaking: #72378 - Removed CSS Styled Content TypoScript for 6.2
================================================================

See :issue:`72378`

Description
===========

The compatibility TypoScript code for CSS Styled Content, which renders the Frontend output to behave like TYPO3 CMS 6.2, has been removed.


Impact
======

Referencing the file via sys_template or including the TypoScript files in `EXT:css_styled_content/static/6.2/` will not work anymore.


Affected Installations
======================

Any installation that still uses the compatibility TypoScript code from TYPO3 CMS 6.2.


Migration
=========

Use the current TypoScript used in CSS Styled Content.

.. index:: TypoScript, Frontend, ext:css_styled_content
