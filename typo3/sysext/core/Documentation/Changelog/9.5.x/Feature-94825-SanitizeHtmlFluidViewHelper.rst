.. include:: ../../Includes.txt

======================================================
Feature: #94825 - New f:sanitize.html Fluid ViewHelper
======================================================

See :issue:`94825`

Description
===========

A new Fluid ViewHelper :html:`<f:sanitize.html>` is available
for use in any Fluid Template.

Unlike :html:`<f:format.html>` the new ViewHelper does not fully rewrite
the contents of the ViewHelper, but only cleans the HTML for
incorrect / possibly bad code.

The htmlSanitize keeps all HTML code as is, but cleans up invalid
and malicious code based on the third-party package `typo3/html-sanitize`.

An optional view-helper argument `build` allows using a defined preset, or a
fully qualified class name of a builder instance as alternative, which has
to implement :php:`\TYPO3\HtmlSanitizer\Builder\BuilderInterface`.
If not given, the configuration falls back to the best-practice
sanitization preset for TYPO3's base RTE configuration (called "default").

Impact
======

The "default" preset of :html:`<f:sanitize.html>` allows
to explicitly sanitize user-submitted markup - for instance provided in
rich-text input fields using the TYPO3 backend user interface. The
default preset only supports common HTML tags and attributes that usually are
expected to be safe - for instance :html:`<iframe>`, :html:`<form>`, :html:`<nav>` or
similar elements are not supported (currently) and not supposed to be defined
by users or editors, but rather by the actual HTML Template which shouldn't
be fully wrapped in :html:`<f:sanitize.html>`.

When to use the different ViewHelpers:

* :html:`<f:format.html>`

Use this for wrapping fields produced by RTE fields, which parses
HTML and adds attributes, replaces TYPO3-internal links to pages or files,
based on :ts:`lib.parseFunc_RTE`. For this reason, it is recommended to use
this ViewHelper mainly in Frontend rendering.

This ViewHelper calls TYPO3's "parseFunc", which means that `htmlSanitize` is
activated by default in TYPO3 installations.

Summarized: :html:`<f:format.html>` does HTML sanitization plus rebuilding
the HTML output based on the configuration from `lib.parseFunc`.

* :html:`<f:sanitize.html>`

This ViewHelper takes the HTML as is, and removes malicious HTML code. This is
useful for HTML returned from external sources where the HTML-based content is
untrusted. It can be used in Frontend and Backend environments.

* :html:`<f:format.raw>`

This ViewHelper just outputs the content as is, including all HTML. Use this
ViewHelper only if the content can be fully trusted.

All of the ViewHelpers above do not escape any of the contents.

.. index:: Fluid, ext:fluid
