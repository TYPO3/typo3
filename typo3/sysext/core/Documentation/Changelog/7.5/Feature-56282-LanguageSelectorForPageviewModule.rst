=======================================================
Feature: #56282 - Language selector for pageview module
=======================================================

Description
===========

The pageview module now has a dropdown to select a language for the page preview.

In case you switch languages based on something different than a parameter called ``L`` you can disable the selector by using the following PageTSConfig:

``mod.SHARED.view.disableLanguageSelector = 1``