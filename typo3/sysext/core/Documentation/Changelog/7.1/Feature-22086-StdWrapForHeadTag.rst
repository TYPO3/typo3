
.. include:: ../../Includes.txt

=====================================================
Feature: #22086 - Add .stdWrap to page.headTag option
=====================================================

See :issue:`22086`

Description
===========

The TypoScript setting page.headTag now has stdWrap functionality available.

The new option can be set like this:

.. code-block:: typoscript

	page = PAGE
	page.headTag = <head>
	page.headTag.override = <head class="special">
	page.headTag.override.if {
	  isInList.field = uid
	  value = 24
	}
