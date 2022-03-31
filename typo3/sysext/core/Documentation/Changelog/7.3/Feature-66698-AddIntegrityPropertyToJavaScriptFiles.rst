
.. include:: /Includes.rst.txt

============================================================
Feature: #66698 - Add integrity property to JavaScript files
============================================================

See :issue:`66698`

Description
===========

Add a property `integrity="some-hash"` to JavaScript files via TypoScript

`page.includeJSLibs.<array>.integrity = some-hash`

This patch affects the TypoScript PAGE properties

* includeJSLibs
* includeJSFooterlibs
* includeJS
* includeJSFooter

Usage:
------

.. code-block:: typoscript
	:emphasize-lines: 6

	page {
		includeJS {
			jQuery = fileadmin/jquery-1.10.2.min.js
			jQuery.disableCompression = 1
			jQuery.excludeFromConcatenation = 1
			jQuery.integrity = sha256-C6CB9UYIS9UJeqinPHWTHVqh/E1uhG5Twh+Y5qFQmYg=
		}
	}

.. hint::
	Integrity hashes may be generated using https://www.srihash.org/.


.. index:: JavaScript, TypoScript, Frontend
