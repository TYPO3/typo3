
.. include:: /Includes.rst.txt

=======================================================
Feature: #56282 - Language selector for pageview module
=======================================================

See :issue:`56282`

Description
===========

The page->view module now has a dropdown to select a language for the page preview.

In case you switch languages based on something different than a parameter called `L` you
can disable the selector by using the following PageTSConfig:

`mod.SHARED.view.disableLanguageSelector = 1`


.. index:: TSConfig, Backend
