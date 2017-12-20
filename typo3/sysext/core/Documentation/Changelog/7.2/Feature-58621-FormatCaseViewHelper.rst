
.. include:: ../../Includes.txt

======================================
Feature: #58621 - FormatCaseViewHelper
======================================

See :issue:`58621`

Description
===========

Add a format case view helper to change casing of strings.

Possible modes are:
* `upper` Transforms the input string to its uppercase representation
* `lower` Transforms the input string to its lowercase representation
* `capital` Transforms the input string to its first letter upper-cased
* `uncapital` Transforms the input string to its first letter lower-cased


.. code-block:: html

	<f:format.case>Some Text with miXed case</f:format.case> renders "SOME TEXT WITH MIXED CASE"

	<f:format.case mode="capital">someString</f:format.case> renders "SomeString"


Impact
======

The new ViewHelper can be used in all new projects. There is no interference with any part of existing code.


.. index:: Fluid
