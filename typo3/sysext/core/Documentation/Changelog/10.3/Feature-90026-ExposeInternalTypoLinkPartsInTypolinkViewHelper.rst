.. include:: /Includes.rst.txt

=====================================================================
Feature: #90026 - Expose internal typoLinkParts in TypolinkViewHelper
=====================================================================

See :issue:`90026`

Description
===========

Parameters being generated internally by TypoLink using
:html:`<f:link.typolink parts-as="typoLinkParts">` view helper are exposed as
variable and can be used in Fluid templates.

View helper attribute :html:`parts-as` (default :html:`typoLinkParts`) allows to define the
variable name to be used containing the following internal parts:

* url
* target
* class
* title
* additionalParams

Details for these internal parts are documented for :typoscript:`typolink.parameter`
in `TypoScript reference`_

.. _TypoScript reference: https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/Functions/Typolink.html?highlight=typolink#parameter

Impact
======

Multiple instructions for attribute :html:`parameter` (e.g. persisted to entity
record) can be used individually.

.. code-block:: html

   <f:link.typolink parameter="123 _top news title" parts-as="parts">
      {parts.url}
      {parts.target}
      {parts.class}
      {parts.title}
      {parts.additionalParams}
   </f:link.typolink>

.. index:: Fluid, Frontend, ext:fluid
