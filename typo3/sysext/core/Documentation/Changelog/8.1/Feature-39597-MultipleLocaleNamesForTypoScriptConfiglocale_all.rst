
.. include:: ../../Includes.txt

========================================================================
Feature: #39597 - Multiple locale names for TypoScript config.locale_all
========================================================================

See :issue:`39597`

Description
===========

The TypoScript option `config.locale_all` now allows to set locale fallbacks as a comma-separated list, as the
underlying PHP function `setlocale()` does as well.

.. code-block:: typoscript

   config.locale_all = de_AT@euro, de_AT, de_DE, deu_deu

..

.. index:: TypoScript, Frontend