
.. include:: ../../Includes.txt

===========================================================
Feature: #61529 - Add multiple parameter to f:form.checkbox
===========================================================

See :issue:`61529`

Description
===========

Introduce parameter "multiple" for f:form.checkbox ViewHelper.

.. code-block:: html

	<f:form action="create" method="POST" name="pizza" object="{pizza}">
		<f:form.checkbox property="covering" multiple="1" value="salami" /><br />
		<f:form.checkbox property="covering" multiple="1" value="ham" /><br />
		<f:form.checkbox property="covering" multiple="1" value="cheese" /><br />
		<f:form.submit value="Send" />
	</f:form>


Impact
======

If you add the parameter "multiple" to your checkboxes, it automatically
appends [] to the name of your checkbox.


.. index:: Fluid
