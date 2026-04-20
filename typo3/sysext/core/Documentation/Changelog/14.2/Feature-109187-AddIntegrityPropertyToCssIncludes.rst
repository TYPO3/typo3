..  include:: /Includes.rst.txt

..  _feature-109187-1774695399:

=========================================================
Feature: #109187 - Add integrity property to CSS includes
=========================================================

See :issue:`109187`

Description
===========

The TypoScript properties :typoscript:`includeCSS` and
:typoscript:`includeCSSLibs` now support the :typoscript:`integrity`
property for Subresource Integrity (SRI) checking, bringing CSS includes
to parity with existing SRI support in :typoscript:`includeJS`,
:typoscript:`includeJSFooter`, :typoscript:`includeJSLibs`, and
:typoscript:`includeJSFooterlibs`.

The :typoscript:`crossorigin` property is also supported. When
:typoscript:`integrity` is set without an explicit
:typoscript:`crossorigin` value for URI resources (for example, external
URLs), :typoscript:`crossorigin="anonymous"` is automatically added,
which is required for SRI validation to work cross-origin.

The :typoscript:`integrity` attribute has not yet been added to inline styles
(:typoscript:`inline = 1`), as SRI only applies to external
resources.

The :php:`PageRenderer::addCssFile()` and
:php:`PageRenderer::addCssLibrary()` methods have gained two new
parameters, :php:`$integrity` and :php:`$crossorigin`.

Impact
======

It is now possible to add SRI integrity hashes to CSS files included via
TypoScript:

..  code-block:: typoscript

    page.includeCSS {
        main = https://cdn.example.com/styles/main.css
        main.integrity = sha384-abc123==
        # crossorigin is auto-set to "anonymous" when integrity is given
    }

    page.includeCSSLibs {
        vendor = https://cdn.example.com/vendor.css
        vendor.integrity = sha384-xyz789==
        vendor.crossorigin = anonymous
    }

This results in the following HTML output:

..  code-block:: html

    <link rel="stylesheet" href="https://cdn.example.com/styles/main.css" media="all" integrity="sha384-abc123==" crossorigin="anonymous">

..  note::

    The integrity hash value can be generated using browser
    developer tools or command-line tools. See `Subresource Integrity on
    MDN <https://developer.mozilla.org/en-US/docs/Web/Security/Defenses/Subresource_Integrity>`__
    for details on how to generate and use integrity hashes.

..  index:: Frontend, TypoScript, ext:frontend
