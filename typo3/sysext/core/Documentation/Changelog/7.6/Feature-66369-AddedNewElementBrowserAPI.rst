
.. include:: ../../Includes.txt

===============================================
Feature: #66369 - Added new element browser API
===============================================

See :issue:`66369`

Description
===========

The former code monster class `ElementBrowser` has been split into dedicated parts of functionality.
Specifically the functionality of selecting elements for the FormEngine and the code parts for creating
links, used in FormEngine and RTE, have been moved into separate APIs.

Each type of element, which can be selected in FormEngine, has its own element browser class.
You may add your own special type by registering your own element browser in your `ext_tables.php` as follows:

.. code:: php

	if (TYPO3_MODE === 'BE') {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers'][<identifier>] = \Vendor\Ext\TheClass::class;
	}

The registered class is expected to implement the `\TYPO3\CMS\Recordlist\Browser\ElementBrowserInterface` interface.
