
.. include:: ../../Includes.txt

==========================================================================
Feature: #51905 - Add dependencies between classes in the Rich Text Editor
==========================================================================

See :issue:`51905`

Description
===========

It is now possible to configure a class as requiring other classes.

The syntax of this new property is

.. code-block:: typoscript

	RTE.classes.[ *classname* ] {
		.requires = list of class names; list of classes that are required by the class;
            		if this property, in combination with others, produces a circular relationship, it is ignored;
            		when a class is added on an element, the classes it requires are also added, possibly recursively;
            		when a class is removed from an element, any non-selectable class that is not required by any of the classes remaining on the element is also removed.
	}


Impact
======

There is no impact on previous configurations.
