.. include:: ../../Includes.txt

========================================================================
Feature: #85550 - Introduce context for TypoScript data getText property
========================================================================

See :issue:`85550`

Description
===========

The new context API can now be accessed via the :ts:`getText` property in TypoScript.

Example:

.. code-block:: typoscript

	page.10 = TEXT
	page.10.data = context:workspace:id
	page.10.wrap = You are in workspace: |

Where as `context` is the keyword for accessing an aspect, the second part is the name of the aspect,
and the third part is the property of the aspect.

.. code-block:: typoscript

	data = context:[aspectName]:[propertyName]

If a property is an array, it is converted into a comma-separated list.

.. index:: TypoScript, ext:frontend