.. include:: ../../Includes.txt

=====================================================
Feature: #57331 - Support dash in CurrencyViewHelper
=====================================================

See :issue:`57331`

Description
===========

The `useDash` option is added to the CurrencyViewHelper.


Impact
======

If the option `useDash` is set and a round value is given, the decimal place is rendered as a dash.

Example:

.. code-block:: html

	<!-- Renders "54321.-" -->
	<f:format.currency useDash="1">54321.00</f:format.currency>

.. index:: Fluid, ext:fluid
