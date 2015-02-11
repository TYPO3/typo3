========================================================
Feature: #28382 - Add async property to JavaScript files
========================================================

Description
===========

Add a property ``async="async"`` to JavaScript files via TypoScript

``page.includeJSlibs.<array>.async = 1``

This patch affects the TypoScript PAGE properties

* includeJSLibs
* includeJSFooterlibs
* includeJS
* includeJSFooter

Usage:
------

.. code-block:: typoscript

	page {
		includeJS {
			jsFile = /Path/To/jsFile.js
			jsFile.async = 1
		}
	}

..

