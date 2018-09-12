.. include:: ../../Includes.txt

==============================================================
Feature: #82091 - Allow inline rendering in SVG content object
==============================================================

See :issue:`82091`

Description
===========

The SVG content object supports a new option to render a SVG file as :html:`<svg>` tag.
The new setting :typoscript:`renderMode` can be set to `inline` to render an inline version of the SVG file.
The :ts:`renderMode` property additionally has :ts:`stdWrap` capabilities.

.. code-block:: typoscript

   page.10 = SVG
   page.10 {
      renderMode = inline
      src = fileadmin/foo.svg
   }

Impact
======

SVG can now be rendered as :html:`<svg>` tag.

.. index:: Frontend, TypoScript
