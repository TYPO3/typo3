.. include:: /Includes.rst.txt

=================================================
Feature: #81223 - includeCSS.inline property
=================================================

See :issue:`81223`

Description
===========

A property :typoscript:`.inline` has been added to :typoscript:`page.includeCSS`.
If :typoscript:`.inline` is set, the content of the css-file is inlined using <style>-tags.


Impact
======

Example:

.. code-block:: typoscript

   page.includeCSS {
      inline = EXT:test/Resources/Public/Css/inline.css
      inline {
         inline = 1
         forceOnTop = 1
         media = all
      }
      other = EXT:test/Resources/Public/Css/other.css
   }


Some notes on the implementation:

External files are not inlined.
The inline-css is compressed if :typoscript:`config.compressCss` is set.
Most other properties (:typoscript:`.allWrap`, :typoscript:`.disableCompression`, :typoscript:`.forceOnTop`, :typoscript:`.if`,
:typoscript:`.media`, :typoscript:`.title`) work even if :typoscript:`.inline` is set.
If :typoscript:`.import` and :typoscript:`.inline` are both set , the file is loaded via @import.

.. index:: Frontend, TypoScript
