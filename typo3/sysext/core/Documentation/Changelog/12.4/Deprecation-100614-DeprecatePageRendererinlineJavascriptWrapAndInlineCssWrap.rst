.. include:: /Includes.rst.txt

.. _deprecation-100614-1681589901:

=======================================================================================
Deprecation: #100614 - Deprecate PageRenderer::$inlineJavascriptWrap and $inlineCssWrap
=======================================================================================

See :issue:`100614`

Description
===========

The protected properties :php:`$inlineJavascriptWrap` and :php:`$inlineCssWrap`
of the class :php:`\TYPO3\CMS\Core\Page\PageRenderer` have been deprecated and
shall not be used any longer.


Impact
======

:php:`PageRenderer` specifics concerning rendering XHTML or non-HTML5 content are
not working any longer in affected installations having custom code extending
:php:`\TYPO3\CMS\Core\Page\PageRenderer`.


Affected installations
======================

Installations with custom code extending :php:`\TYPO3\CMS\Core\Page\PageRenderer`
that are reading from or writing to the mentioned protected properties
:php:`$inlineJavascriptWrap` or :php:`$inlineCssWrap`.


Migration
=========

Avoid using the protected properties :php:`$inlineJavascriptWrap` and
:php:`$inlineCssWrap`. In case any custom code needs to wrap with inline
:html:`<script>` or :html:`<style>` tags, use the new protected methods
:php:`wrapInlineScript($content)` and :php:`wrapInlineStyle($content)`
within :php:`\TYPO3\CMS\Core\Page\PageRenderer`.


.. index:: Frontend, Backend, NotScanned, ext:core
