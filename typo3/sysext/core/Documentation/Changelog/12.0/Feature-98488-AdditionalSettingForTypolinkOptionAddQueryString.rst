.. include:: /Includes.rst.txt

.. _feature-98488-1664578785:

=========================================================================
Feature: #98488 - Additional setting for Typolink option "addQueryString"
=========================================================================

See :issue:`98488`

Description
===========

The Typolink option :typoscript:`typolink.addQueryString` now also accepts
the value `untrusted` to be used to retrieve all GET parameters of the current request.

This value can also used in the Fluid ViewHelpers
:html:`<f:link.typolink>`, :html:`<f:uri.typolink>`, :html:`<f:link.page>`,
:html:`<f:uri.page>`, :html:`<f:link.action>`, :html:`<f:link.action>` and
:html:`<f:form>`.

Impact
======

Setting :typoscript:`typolink.addQueryString = untrusted` adds any given query parameters
just as it was done in TYPO3 v11 when using :typoscript:`typolink.addQueryString = 1`.

.. index:: Fluid, TypoScript, ext:frontend
