.. include:: ../../Includes.txt

============================================================================
Feature: #82704 - Add readonly and required attributes to TextareaViewHelper
============================================================================

See :issue:`82704`

Description
===========

The view helper `f:form.textarea` now supports the attributes `readonly` and `required`.


Impact
======

The attributes `readonly` and `required` may be set by using the `f:form.textarea` view helper.

Example:

.. code-block:: html

	<!-- Set required attribute -->
	<f:form.textarea name="foobar" required="1" />

	<!-- Set readonly attribute -->
	<f:form.textarea name="foobar" readonly="1" />

.. index:: Fluid
