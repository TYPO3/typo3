.. include:: /Includes.rst.txt

.. _feature-103560-1712562637:

==========================================================
Feature: #103560 - Update Fluid Standalone to version 2.11
==========================================================

See :issue:`103560`

Description
===========

Fluid Standalone has been updated to version 2.11. This version includes new
ViewHelpers that cover common tasks in Fluid templates. More ViewHelpers will
be added with future minor releases.

A full documentation of the new ViewHelper's arguments is available in the
ViewHelper reference <https://docs.typo3.org/other/typo3/view-helper-reference/main/en-us/>.


Impact
======

The following ViewHelpers are now included and can be used in all Fluid
templates:

:html:`<f:split>` ViewHelper:
-----------------------------

The :php:`SplitViewHelper` splits a string by the specified separator, which
results in an array.

.. code-block:: html

    <f:split value="1,5,8" separator="," /> <!-- Output: {0: '1', 1: '5', 2: '8'} -->
    <f:split separator="-">1-5-8</f:split> <!-- Output: {0: '1', 1: '5', 2: '8'} -->
    <f:split value="1,5,8" separator="," limit="2" /> <!-- Output: {0: '1', 1: '5,8'} -->


:html:`<f:join>` ViewHelper:
----------------------------

The :php:`JoinViewHelper` combines elements from an array into a single string.

.. code-block:: html

    <f:join value="{0: '1', 1: '2', 2: '3'}" /> <!-- Output: 123 -->
    <f:join value="{0: '1', 1: '2', 2: '3'}" separator=", " /> <!-- Output: 1, 2, 3 -->
    <f:join value="{0: '1', 1: '2', 2: '3'}" separator=", " separatorLast=" and " /> <!-- Output: 1, 2 and 3 -->


:html:`<f:replace>` ViewHelper:
-------------------------------

The :php:`ReplaceViewHelper` replaces one or multiple strings with other strings.

.. code-block:: html

    <f:replace value="Hello World" search="World" replace="Fluid" /> <!-- Output: Hello Fluid -->
    <f:replace value="Hello World" search="{0: 'World', 1: 'Hello'}" replace="{0: 'Fluid', 1: 'Hi'}" /> <!-- Output: Hi Fluid -->
    <f:replace value="Hello World" replace="{'World': 'Fluid', 'Hello': 'Hi'}" /> <!-- Output: Hi Fluid -->


:html:`<f:first>` and :html:`<f:last>` ViewHelpers:
---------------------------------------------------

The :php:`FirstViewHelper` and :php:`LastViewHelper` return the first or last
item of a specified array, respectively.

.. code-block:: html

    <f:first value="{0: 'first', 1: 'second', 2: 'third'}" /> <!-- Outputs "first" -->
    <f:last value="{0: 'first', 1: 'second', 2: 'third'}" /> <!-- Outputs "third" -->


.. index:: Fluid, Frontend, ext:fluid
