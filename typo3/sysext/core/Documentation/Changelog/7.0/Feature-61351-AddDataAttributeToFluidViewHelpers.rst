
.. include:: ../../Includes.txt

=========================================================
Feature: #61351 - Add data attribute to Fluid ViewHelpers
=========================================================

See :issue:`61351`

Description
===========

Since HTML5 Elements can contain a generic data attribute,
Fluid provides for those elements the possibility to add
key-value pairs as array, which will be rendered as
`data-$key="$value"`.

.. code-block:: html

	<f:form.textfield data="{foo: 'bar', baz: 'foos'}" />

Impact
======

Generic data attributes do not need to be passed by the
`additionalAttributes` array anymore making the viewhelper
more straightforward to use.
