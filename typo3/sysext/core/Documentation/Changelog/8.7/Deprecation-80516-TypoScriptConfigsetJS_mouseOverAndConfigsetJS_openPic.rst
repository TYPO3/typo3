.. include:: /Includes.rst.txt

================================================================================
Deprecation: #80516 - TypoScript config.setJS_mouseOver and config.setJS_openPic
================================================================================

See :issue:`80516`

Description
===========

The TypoScript properties :typoscript:`config.setJS_mouseOver` and :typoscript:`config.setJS_openPic` have been marked
as deprecated.


Impact
======

Setting any of the TypoScript properties will trigger a deprecation log entry.


Affected Installations
======================

Any installation using these TypoScript options.


Migration
=========

Include the small JavaScript files directly in your custom JavaScript file or inline via :typoscript:`page.inlineJS`.

.. index:: TypoScript, Frontend
