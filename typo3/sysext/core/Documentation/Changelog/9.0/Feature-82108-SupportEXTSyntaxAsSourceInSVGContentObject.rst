.. include:: ../../Includes.txt

=====================================================================
Feature: #82108 - Support EXT: syntax as source in SVG content object
=====================================================================

See :issue:`82108`

Description
===========

The SVG Content Object property :typoscript:`src` now supports `EXT:` syntax to reference files from extensions.

.. code-block:: typoscript

   page.10 = SVG
   page.10 {
      src = EXT:my_extension/Resources/Public/Icons/foo.svg
   }

Impact
======

A SVG file can now be references by EXT: syntax.

.. index:: Frontend, TypoScript
