==========================================================
Deprecation: #62800 - Workspaces ToolbarItem via ExtDirect
==========================================================

Description
===========

The PHP functionality for switching a workspace was done with the ExtDirect call "TYPO3.Ajax.ExtDirect.ToolbarMenu"
until now. This is now replaced by a simple AJAX JSON call, based on jQuery and the refactored ToolbarItem Menu for
the workspaces module.

Impact
======

The core does not use this functionality anymore, and also removed the ExtDirect registration for this class.


Affected installations
======================

All installations which directly used the ExtDirect "TYPO3.Ajax.ExtDirect.ToolbarMenu" to fetch the data.

Migration
=========

Use the new AjaxHandler "Workspaces::setWorkspace" directly instead.
