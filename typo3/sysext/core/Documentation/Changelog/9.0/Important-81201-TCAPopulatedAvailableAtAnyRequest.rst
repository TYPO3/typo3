.. include:: /Includes.rst.txt

==========================================================
Important: #81201 - TCA populated available at any request
==========================================================

See :issue:`81201`

Description
===========

Evaluating the global :php:`$TCA` array, necessary to do access checks, or database queries, is
now done within the TYPO3 Bootstrap instead of any request handler.

This is possible since TYPO3 v8, because TCA compiling is now completely separated from loading
:file:`ext_tables.php` of an extension, and is also available before instantiating a controller (typically
:php:`TypoScriptFrontendController`) in the frontend.

This leads to the following changes in behaviour:

- TCA compilation is done earlier in the request process. It is handled after :file:`ext_localconf.php` is
  evaluated, but before any further hooks are executed.
- The full TCA is available even when evaluating any RequestHandler.
- The global variable $TCA is now available at the very beginning of an eID request, it is not
  necessary to load TCA via :php:`EidUtility::loadTCA()` anymore.

Side Note: This does not affect the install tool as it does a custom set-up of the TYPO3 Bootstrap.

.. index:: PHP-API, TCA
