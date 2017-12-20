.. include:: ../../Includes.txt

===============================================================
Feature: #79622 - New Content Elements for Fluid Styled Content
===============================================================

See :issue:`79622`

Description
===========

Content Elements from CSS Styled Content that were previously not supported
by Fluid Styled Content are now making their comeback in order to share
the same feature set across both content element renderings.

=================   ==========   =================================================================
Name                cType        Description
=================   ==========   =================================================================
Text                text         A regular text element with header and bodytext fields.
Text and Images     textpic      Any number of images wrapped right around a regular text element.
Images              image        Any number of images aligned in columns and rows with a caption.
=================   ==========   =================================================================


Impact
======

The content elements Text, Text and Images, Images are now also available
when Fluid Styled Content is used as content rendering definition.


.. index:: Frontend, ext:fluid_styled_content, TypoScript
