.. include:: ../../Includes.txt

============================================================
Feature: #78002 - Enforce cHash argument for Extbase actions
============================================================

See :issue:`78002`

Description
===========

`TypoScriptFrontendController::reqCHash()` is now called for Extbase frontend
plugin actions just like they are usually called for AbstractPlugin.
This provides a more reliable page caching behavior by default and with zero
configuration for extension authors.

With the feature switch `requireCHashArgumentForActionArguments` this behavior
can be disabled, which could be useful, if all actions in a plugin are uncached
or one wants to manually control the cHash behavior.


Impact
======

The enforcing of a cHash results in a 404, if plugin arguments are present but
cHash is not, which would also happen if the plugin arguments were added to
`cHashRequiredParameters` configuration.

.. index:: Frontend, PHP-API, ext:extbase