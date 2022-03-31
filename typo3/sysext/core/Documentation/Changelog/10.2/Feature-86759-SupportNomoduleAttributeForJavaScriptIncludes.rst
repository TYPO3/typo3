.. include:: /Includes.rst.txt

====================================================================
Feature: #86759 - Support nomodule attribute for JavaScript includes
====================================================================

See :issue:`86759`

Description
===========

When including JavaScript files in TypoScript, the HTML5 attribute :html:`nomodule` is now
supported.

See https://html.spec.whatwg.org/multipage/scripting.html#attr-script-nomodule

.. code-block:: typoscript

   page.includeJSFooter.file = path/to/file.js
   page.includeJSFooter.file.nomodule = 1

.. index:: TypoScript
