
.. include:: ../../Includes.txt

======================================================
Feature: #16525 - Add conditions to INCLUDE_TYPOSCRIPT
======================================================

See :issue:`16525`

Description
===========

The INCLUDE_TYPOSCRIPT tag now has an extra (optional) property "condition" which causes the file/directory to be included only
if the condition is met.

As usual a condition is enclosed in square brackets, but if these are not present they will be added. Any double quotes must be
escaped by adding backslashes and any backslash must be doubled.

Example
-------

.. code-block:: typoscript

   <INCLUDE_TYPOSCRIPT: source="FILE:EXT:my_extension/Configuration/TypoScript/firefox.ts" condition="[loginUser = *]">

Condition with square brackets. File will only be included if a frontend user is logged in.

.. code-block:: typoscript

   <INCLUDE_TYPOSCRIPT: source="FILE:EXT:my_extension/Configuration/TypoScript/staging.ts" condition="applicationContext = /^Production\\/Staging\\/Server\\d+$/">

Condition without square brackets, backslashes doubled inside the condition. File will only be included in application context
Production/Staging/Server followed by at least one digit.


.. index:: TypoScript, Frontend
