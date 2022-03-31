.. include:: /Includes.rst.txt

=================================================================================
Feature: #78415 - Global Fluid ViewHelper namespaces moved to TYPO3 configuration
=================================================================================

See :issue:`78415`

Description
===========

By storing Fluid's namespaces in `$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']` we can allow adding or
extending the global namespaces from third party packages in for example :file:`ext_localconf.php` or by simply specifying
the namespace arrays in :file:`LocalConfiguration.php`.

In terms of performance there is nearly zero impact but in terms of flexibility this should provide the ultimate way
to manage global namespaces as configuration; something that currently is only possible by implementing custom
ViewHelperResolvers.


Impact
======

* Site administrators and third party ViewHelper packages will be able to manipulate the global
  namespace `f:` in configuration
* Third party ViewHelper packages will be able to register new global namespaces
* Template developers can use such global namespaces without first importing them and can use them
  in all Fluid templates regardless of context.

.. index:: Fluid, LocalConfiguration, PHP-API
