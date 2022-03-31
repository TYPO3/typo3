.. include:: /Includes.rst.txt

============================================================================
Feature: #82999 - Add a hook to hide credentials in the Configuration module
============================================================================

See :issue:`82999`

Description
===========

To blind additional configuration options in the Configuration module a hook has been added:

:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Lowlevel\ControllerConfigurationController::class]['modifyBlindedConfigurationOptions']`


Impact
======

Extension developers can use this hook to e.g. hide custom credentials in the Configuration module.

.. index:: PHP-API
