
.. include:: ../../Includes.txt

===============================================
Feature: #62242 - ActionMenuItemGroupViewHelper
===============================================

See :issue:`62242`

Description
===========

Using this ViewHelper, OptGroups can be used in the backend select field, which controls which action is selected.


Impact
======

The new ViewHelper can be used in all new projects. There is no interference with any part of existing code.


Examples
========

Usage example:

.. code-block:: html

	<f:be.menus.actionMenu>
		<f:be.menus.actionMenuItem label="Default: Welcome" controller="Default" action="index" />
		<f:be.menus.actionMenuItem label="Community: get in touch" controller="Community" action="index" />

		<f:be.menus.actionMenuItemGroup label="Information">
			<f:be.menus.actionMenuItem label="PHP Information" controller="Information" action="listPhpInfo" />
			<f:be.menus.actionMenuItem label="Documentation" controller="Information" action="documentation" />
			<f:be.menus.actionMenuItem label="Hooks" controller="Information" action="hooks" />
			<f:be.menus.actionMenuItem label="Signals" controller="Information" action="signals" />
			<f:be.menus.actionMenuItem label="XClasses" controller="Information" action="xclass" />
		</f:be.menus.actionMenuItemGroup>
	</f:be.menus.actionMenu>
