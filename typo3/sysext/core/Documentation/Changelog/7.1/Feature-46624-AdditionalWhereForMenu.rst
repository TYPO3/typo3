
.. include:: ../../Includes.txt

==========================================================
Feature: #46624 - HMENU item selection via additionalWhere
==========================================================

See :issue:`46624`

Description
===========

The TypoScript Content Object HMENU menu options have a new property called "additionalWhere" to
allow for a more specific database query based on any page properties.

.. code-block:: typoscript

	lib.authormenu = HMENU
	lib.authormenu.1 = TMENU
	lib.authormenu.1.additionalWhere = AND author!=""
	...
