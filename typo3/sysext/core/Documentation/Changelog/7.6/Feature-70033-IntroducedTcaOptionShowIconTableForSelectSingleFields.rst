
.. include:: ../../Includes.txt

=============================================================================
Feature: #70033 - Introduced TCA option showIconTable for selectSingle fields
=============================================================================

See :issue:`70033`

Description
===========

A new option `showIconTable` has been introduced for select fields with render type `selectSingle` to enforce or prevent the
icon table underneath the field. By default the icon table is not shown.

Example
-------

.. code-block:: php

	// Enforce icon table showing flags
	$GLOBALS['TCA']['tt_content']['columns']['sys_language_uid']['config']['showIconTable'] = true;

