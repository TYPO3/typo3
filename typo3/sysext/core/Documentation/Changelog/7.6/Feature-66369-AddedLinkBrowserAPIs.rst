
.. include:: /Includes.rst.txt

========================================
Feature: #66369 - Added LinkBrowser APIs
========================================

See :issue:`66369`

Description
===========

This new feature allows to extend the link browser with new tabs, which allow to implement custom link functionality
in a generic way in a so called LinkHandler.
Since the LinkBrowser is used by FormEngine and RTE, the new API ensures that your custom LinkHandler works with those
two, and possible future, usages flawlessly.

Each tab rendered in the link browser has an associated link handler, responsible for rendering the tab and for creating
and editing of links belonging to this tab.


Tab registration
----------------

Link browser tabs are registered in page TSconfig like this:

.. code-block:: typoscript

	TCEMAIN.linkHandler.<tabIdentifier> {
		handler = TYPO3\CMS\Recordlist\LinkHandler\FileLinkHandler
		label = LLL:EXT:lang/locallang_browse_links.xlf:file
		displayAfter = page
		scanAfter = page
		configuration {
			customConfig = passed to the handler
		}
	}

The options `displayBefore` and `displayAfter` define the order how the various tabs are displayed in the link browser.

The options `scanBefore` and `scanAfter` define the order in which handlers are queried when determining the responsible
tab for an existing link.
Most likely your links will start with a specific prefix to identify them. Therefore you should register your tab at least before
the 'url' handler, so your handler can advertise itself as responsible for the given link.
The 'url' handler should be treated as last resort as it will work with any link.


Handler implementation
----------------------

A link handler has to implement the `\TYPO3\CMS\Recordlist\LinkHandler\LinkHandlerInterface` interface, which defines
all necessary methods for communication with the link browser.

Additionally, each link handler should also provide a Javascript module (requireJS), which takes care of passing a link
to the link browser.
A minimal implementation of such a module looks like this:

.. code-block:: javascript

	define(['jquery', 'TYPO3/CMS/Recordlist/LinkBrowser'], function($, LinkBrowser) {

		var myModule = {};

		myModule.createMyLink = function() {
			var val = $('.myElmeent').val();

			// optional: If your link points to some external resource you should set this attribute
			LinkBrowser.setAdditionalLinkAttribute('data-htmlarea-external', '1');

			LinkBrowser.finalizeFunction('mylink:' + val);
		};

		myModule.initialize = function() {
			// todo add necessary event handlers, which will propably call myModule.createMyLink
		};

		$(myModule.initialize);

		return myModule;
	}

Notice the call to `LinkBrowser.finalizeFunction`, which is the point where the link is handed over to the link browser
for further processing and storage.


Hooks
-----

You may have the need to modify the list of available link handlers based on some dynamic value.
For this purpose you can register hooks.

The registration of a link browser hook generally happens in your `ext_tables.php` and looks like:

.. code-block:: php

	if (TYPO3_MODE === 'BE') {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['LinkBrowser']['hooks'][1444048118] = [
			'handler' => \Vendor\Ext\MyClass::class,
			'before' => [], // optional
			'after' => [] // optional
		];
	}

The `before` and `after` elements allow to control the execution order of all registered hooks.

Currently the following list of hooks is implemented:

- modifyLinkHandlers(linkHandlers, currentLinkParts): May modify the list of available link handlers and has to return the final list.
- modifyAllowedItems(allowedTabs, currentLinkParts): May modify the list of available tabs and has to return the final list.


.. index:: PHP-API, Backend, TSConfig, JavaScript
