========================================
Feature: #66369 - Added LinkBrowser APIs
========================================

Description
===========

This new feature allows to extend the LinkBrowser with new tabs.

Each tab is handled by a so called LinkHandler, which has to implement the ``\TYPO3\CMS\Recordlist\LinkHandler\LinkHandlerInterface``.

The LinkHandlers are registered in page TSconfig like:

.. code:: typoscript

	file {
		handler = TYPO3\\CMS\\Recordlist\\LinkHandler\\FileLinkHandler
		label = LLL:EXT:lang/locallang_browse_links.xlf:file
		displayAfter = page
		scanAfter = page
		configuration {
			customConfig = passed to the handler
		}
	}

The handlers are displayed as tabs in the link browser.
The options ``displayBefore`` and ``displayAfter`` define the order of the displayed tabs.

The options ``scanBefore`` and ``scanAfter`` define the order in which handlers are executed when scanning existing links.
For instance, your links might start with a specific prefix to identify them. Therefore you should register at least before
the 'url' handler, so your handler can advertise itself as responsible for the given link.

.. code:: php

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['LinkBrowser']['hooks'][1444048118] = [
		'handler' => \Vendor\Ext\MyClass::class,
		'before' => [], // optional
		'after' => [] // optional
	];
