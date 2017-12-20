
.. include:: ../../Includes.txt

================================================
Feature: #71196 - Disallow localization mixtures
================================================

See :issue:`71196`

Description
===========

The PageLayout UI will now inform users if a mixture of translated content and standalone content is used in
the page module since this is a major source of confusion for both administrators and editors.

In case an integrator knows what he/she is doing, we introduce a `PageTSConfig` setting to turn these warnings off to
allow further usage of inconsistent translation handling.

`mod.web_layout.allowInconsistentLanguageHandling = 1`


Impact
======

Upon setting `mod.web_layout.allowInconsistentLanguageHandling` to `1` the page module will behave
as before and allow inconsistent mixups of languages in a certain language.


.. index:: TSConfig, Backend
