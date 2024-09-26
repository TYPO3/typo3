.. include:: /Includes.rst.txt

.. _important-103485-1718964758:

===========================================================
Important: #103485 - Provide lib.parseFunc via ext:frontend
===========================================================

See :issue:`103485`

Description
===========

The :typoscript:`lib.parseFunc` and :typoscript:`lib.parseFunc_RTE` functions
render HTML from Rich Text Fields in TYPO3. Direct interaction with these
libraries is now uncommon, but they control the output of the
:html:`<f:format.html>` ViewHelper.

Previously, the libraries were available only through content rendering definitions
like fluid_styled_content, the bootstrap_package or your own packages.

The :html:`<f:format.html>` ViewHelper requires a parseFunc to function and
will throw an exception if none is provided. With the shift towards
self-contained content elements, also known as content blocks, there is no need
to include a separate rendering definition. The frontend provides a base version
of the libraries, which are now available in the frontend context.

The libraries are loaded early in the TypoScript chain, ensuring that all
existing overrides continue to work as before, without the need for a basic
parseFunc definition.

.. index:: Frontend, TypoScript, ext:frontend
