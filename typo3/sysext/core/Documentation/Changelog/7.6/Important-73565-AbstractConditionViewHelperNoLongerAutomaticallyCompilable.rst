
.. include:: /Includes.rst.txt

==================================================================================
Important: #73565 - AbstractConditionViewHelper no longer automatically compilable
==================================================================================

See :issue:`73565`

Description
===========

CompilableInterface is removed from the abstract AbstractConditionViewHelper and
is now implemented by each of the condition ViewHelpers. The base class still provides
every method it did before but third-party ViewHelpers must now indicate that they are
compilable by implementing the required interface.

The change is related to a previous change which made `evaluateCondition` the API
to evaluate the input argument conditions in any condition ViewHelper. The change
is done as a preventative measure, preventing issues when third-party condition
ViewHelpers were compiled but did not implement the `evaluateCondition` method.
Now, such ViewHelpers will prevent compiling entirely until the author of the class
has implemented CompilableInterface and the `evaluateCondition` method.

Making such third-party ViewHelpers no longer compilable (and thus decreasing performance
when they are used) is chosen in favor of preserving the current behavior where such
ViewHelpers would be completely unable to correctly evaluate the condition at all.

.. index:: PHP-API, Fluid
