.. include:: /Includes.rst.txt

=====================================================
Feature: #57331 - Support dash in CurrencyViewHelper
=====================================================

See :issue:`57331`

Description
===========

The :php:`useDash` option has been added to the CurrencyViewHelper.


Impact
======

If the option :php:`useDash` is set and a value without decimals (see example) is given, the decimal place is rendered as a dash.

Example:

.. code-block:: html

	<!-- Renders "54321.-" -->
	<f:format.currency useDash="true">54321.00</f:format.currency>

.. index:: Fluid, ext:fluid
