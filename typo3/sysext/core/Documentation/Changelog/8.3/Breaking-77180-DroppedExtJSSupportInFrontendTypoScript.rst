
.. include:: /Includes.rst.txt

===============================================================
Breaking: #77180 - Dropped ExtJS support in Frontend TypoScript
===============================================================

See :issue:`77180`

Description
===========

The following TypoScript options

.. code-block:: typoscript

	page.javascriptLibs.ExtJs
	page.javascriptLibs.ExtJs.debug
	page.inlineLanguageLabel
	page.extOnReady

have been removed.


Impact
======

Using the settings above will not include ExtJs and inline language labels anymore in the TYPO3 Frontend.


Affected Installations
======================

Any installation using the shipped ExtJS bundle in the frontend.


Migration
=========

Include ExtJS via :typoscript:`page.includeJS` manually if needed or migrate to another supported modern framework.

.. index:: JavaScript, TypoScript
