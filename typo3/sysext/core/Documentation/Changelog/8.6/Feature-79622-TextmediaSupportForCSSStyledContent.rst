.. include:: ../../Includes.txt

==========================================================
Feature: #79622 - Textmedia support for CSS Styled Content
==========================================================

See :issue:`79622`

Description
===========

CSS Styled Content now comes with support for the content element "Text and Media"
that was previously exclusive to Fluid Styled Content, to make the transition from
CSS Styled Content to Fluid Styled content easier.

The "Text and Media" implementation uses the fluid rendering for the Gallery from
the Fluid Styled Content implementation and also the ClickEnlarge ViewHelper.
This is only a temporary solution until we remove CSS Styled Content from the
TYPO3 Core with CMS 9.


Impact
======

"Text and Media" content element is now also available for CSS Styled Content.


.. index:: Frontend, ext:css_styled_content
