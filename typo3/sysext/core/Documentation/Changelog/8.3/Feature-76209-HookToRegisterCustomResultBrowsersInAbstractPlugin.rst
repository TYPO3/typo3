
.. include:: ../../Includes.txt

===========================================================================
Feature: #76209 - Hook to register custom result browsers in AbstractPlugin
===========================================================================

See :issue:`76209`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Frontend\Plugin\AbstractPlugin::class]['pi_list_browseresults']` allows
registering custom result browser implementations. This approach allows to override the default implementation of
:php:`AbstractPlugin::pi_list_browseresults()` for either all extensions or only for specific ones.


Impact
======

The hook may be registered in `ext_localconf.php`:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Frontend\Plugin\AbstractPlugin::class]['pi_list_browseresults'][1463475262] = \Vendor\ExtensionKey\Hook\ResultBrowserHook::class

The registered class must implement the method :php:`pi_list_browseresults()` with the following arguments:

* int `$showResultCount` Determines how the results of the page browser will be shown
* string `$tableParams` Attributes for the table tag which is wrapped around the table cells containing the browse links
* array `$wrapArr` Array with elements to overwrite the default $wrapper-array
* string `$pointerName` Variable name for the pointer
* bool `$hscText` Enable htmlspecialchars() for the pi_getLL function
* bool `$forceOutput` Forces the output of the page browser if you set this option to `true`
* object `$pObj` The AbstractPlugin instance calling the hook

.. index:: PHP-API, LocalConfiguration, Frontend
