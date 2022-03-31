
.. include:: /Includes.rst.txt

================================================================================
Feature: #28243 - Introduce TCA option to disable age display of dates per field
================================================================================

See :issue:`28243`

Description
===========

It is now possible to disable the display of the age (p.e. "2015-08-30 (-27 days)") of date fields in record
listings by a new TCA option.
The option is called `disableAgeDisplay` and can be set in the config section of a field.
It will be respected if the field has the type `input` and its eval is set to `date`.

Example
-------

.. code-block:: php

	// disables the display of " (-27 days)" p.e.
	$GLOBALS['TCA']['tt_content']['columns']['date']['config']['disableAgeDisplay'] = true;


.. index:: TCA, Backend
