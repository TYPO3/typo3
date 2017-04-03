.. include:: ../../Includes.txt

==============================================================================
Feature: #76910 - PageLayoutView - Allow to disable copy- / translate- buttons
==============================================================================

See :issue:`76910`

Description
===========

The localization actions "Translate" and "Copy" are now toggleable by PageTS and UserTS.

.. code-block:: typoscript

	mod.web_layout.localization.enableCopy = 1
	mod.web_layout.localization.enableTranslate = 1


Impact
======

Using these options allows to disable or enable a certain action on user basis and/or page basis.

.. index:: Backend, TSConfig
