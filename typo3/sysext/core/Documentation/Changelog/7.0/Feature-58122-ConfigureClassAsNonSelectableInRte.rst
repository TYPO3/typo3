
.. include:: ../../Includes.txt

=======================================================================
Feature: #58122 - Configure class as non-selectable in Rich Text Editor
=======================================================================

See :issue:`58122`

Description
===========

It is now possible to configure a class as non-selectable in the style selectors of the Rich Text Editor.

The syntax of this new property is

.. code-block:: typoscript

	RTE.classes.[ *classname* ] {
		.selectable = boolean; if set to 0, the class is not selectable in the style selectors; if the property is omitted or set to 1, the class is selectable in the style selectors
	}


Impact
======

There is no impact on previous configurations.


.. index:: RTE, TSConfig, Backend
