.. include:: ../../Includes.txt

==============================================================
Feature: #76459 - Add crossorigin property to JavaScript files
==============================================================

See :issue:`76459`

Description
===========

It is now possible to add the HTML attribute :html:`crossorigin="some-value"` to <script> tags for
Frontend rendering via TypoScript with the following new property

:typoscript:`page.includeJSlibs.<array>.crossorigin = some-value`

The ``crossorigin`` property is automatically set to the value ``anonymous`` for
external JavaScript files with an ``integrity`` property if not explicitly set.

The feature is available within the following TypoScript PAGE properties

*  :typoscript:`includeJSlibs`
*  :typoscript:`includeJSFooterlibs`
*  :typoscript:`includeJS`
*  :typoscript:`includeJSFooter`

Usage:
------

.. code-block:: typoscript

   page {
     includeJS {
       jQuery = https://code.jquery.com/jquery-2.2.4.min.js
       jQuery.external = 1
       jQuery.disableCompression = 1
       jQuery.excludeFromConcatenation = 1
       jQuery.integrity = sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=
       jQuery.crossorigin = anonymous
     }
   }

.. index:: Frontend, TypoScript, JavaScript
