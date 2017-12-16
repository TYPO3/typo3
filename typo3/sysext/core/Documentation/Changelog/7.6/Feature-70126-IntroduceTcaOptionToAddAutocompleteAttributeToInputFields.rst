
.. include:: ../../Includes.txt

====================================================================================
Feature: #70126 - Introduce TCA option to add autocomplete attribute to input fields
====================================================================================

See :issue:`70126`

Description
===========

It is now possible to enforce or disable the auto completion for input fields in edit mode.
The option is called `autocomplete` and can be set to TRUE or FALSE in the config section of a field.

Example
-------

.. code-block:: php

	// Prevent auto completion of username field for be_users records
	$GLOBALS['TCA']['be_users']['columns']['username']['config']['autocomplete'] = FALSE;
