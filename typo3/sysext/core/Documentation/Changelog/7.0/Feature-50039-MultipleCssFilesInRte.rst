
.. include:: /Includes.rst.txt

========================================================
Feature: #50039 - Multiple CSS Files in Rich Text Editor
========================================================

See :issue:`50039`

Description
===========

It is now possible to import more than one CSS file for the Rich Text Editor.

New syntax is:

.. code-block:: typoscript

	RTE.default.contentCSS {
		file1 = fileadmin/myStylesheet1.css
		file2 = fileadmin/myStylesheet2.css
	}


Impact
======

The old syntax may still be used. If no CSS files are set, the RTE default CSS
file is used as before.


.. index:: TSConfig, RTE, Backend
