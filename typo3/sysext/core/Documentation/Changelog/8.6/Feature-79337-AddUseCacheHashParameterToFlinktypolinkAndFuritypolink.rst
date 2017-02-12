.. include:: ../../Includes.txt

==================================================================================
Feature: #79337 - Add useCacheHash parameter to f:link.typolink and f:uri.typolink
==================================================================================

See :issue:`79337`

Description
===========

The older implementation of the two typolink ViewHelpers was lacking support of the useCacheHash parameter.

The boolean argument `useCacheHash` has been added to the typoscript Viewhelpers.

.. code-block:: html

    <f:link.typolink parameter="{link}" useCacheHash="true">

.. index:: Fluid, Frontend
