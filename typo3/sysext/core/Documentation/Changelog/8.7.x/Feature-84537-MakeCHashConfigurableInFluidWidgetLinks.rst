.. include:: ../../Includes.txt

================================================================
Feature: #84537 - Make cHash configurable in Fluid Widget Links
================================================================

See :issue:`84537`

Description
===========

When creating links with fluid widgets it is now possible to disable the cHash calculation.

A new argument `useCacheHash` for the :html:`<f:widget.link>` and the :html:`<f:widget.uri>` ViewHelpers has been added.
By default it is set to `true` to keep the previous behavior.

.. index:: Fluid, NotScanned
