
.. include:: ../../Includes.txt

==================================================================
Feature: #69602 - Simplify handling of backend layouts in frontend
==================================================================

See :issue:`69602`

Description
===========

To avoid complex TypoScript for integrators, the handling of backend layouts has
been simplified for the frontend.

To get the correct backend layout, the following TypoScript code can be used:

.. code-block:: typoscript

	page.10 = FLUIDTEMPLATE
	page.10 {
	  file.stdWrap.cObject = CASE
	  file.stdWrap.cObject {
		key.data = pagelayout

		default = TEXT
		default.value = EXT:sitepackage/Resources/Private/Templates/Home.html

		3 = TEXT
		3.value = EXT:sitepackage/Resources/Private/Templates/1-col.html

		4 = TEXT
		4.value = EXT:sitepackage/Resources/Private/Templates/2-col.html
	  }
	}

Using  `data = pagelayout` is the same as using as

.. code-block:: typoscript

	field = backend_layout
	ifEmpty.data = levelfield:-2,backend_layout_next_level,slide
	ifEmpty.ifEmpty = default
