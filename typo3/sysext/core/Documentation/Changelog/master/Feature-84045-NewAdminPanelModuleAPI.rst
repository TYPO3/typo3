.. include:: ../../Includes.txt

===========================================
Feature: #84045 - new AdminPanel module API
===========================================

See :issue:`84045`

Description
===========

Extending the Admin Panel was only partially possible in earlier TYPO3 versions by using a hook that provided the possibility to add pure content (no new modules) as plain HTML.

A new API has been introduced, providing more flexible options to add custom modules to the admin panel or replace and deactivate existing ones.


Impact
======

Custom admin panel modules can now be registered via `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['frontend']['adminPanelModules']`.

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['frontend']['adminPanelModules']['yourmodulename'] = [
	    'module' => \Vendor\Package\AdminPanel\YourModule::class,
	    'after' => ['preview']
	]

To implement a custom module your module class has to implement the `\TYPO3\CMS\Frontend\AdminPanel\AdminPanelModuleInterface`.

Be aware that the `\TYPO3\CMS\Frontend\AdminPanel\AdminPanelModuleInterface` is not final yet and may change until v9 LTS.

.. index:: Frontend, PHP-API, ext:frontend
