
.. include:: /Includes.rst.txt

==============================================================
Feature: #63453 - Template support for FlashMessagesViewHelper
==============================================================

See :issue:`63453`

Description
===========

Template support for `FlashMessagesViewHelper` has been added.
This allows to define a custom rendering for flash messages.

The new attribute `as` for the `FlashMessagesViewHelper` allows to specify a variable name,
which can be used within the view helper's child elements to access the flash messages.

Example usage:

.. code-block:: html

	<f:flashMessages as="flashMessages">
		<ul class="myFlashMessages">
			<f:for each="{flashMessages}" as="flashMessage">
				<li class="alert {flashMessage.class}">
					<h4>{flashMessage.title}</h4>
					<span class="fancy-icon">{flashMessage.message}</span>
				</li>
			</f:for>
		</ul>
	</f:flashMessages>


.. index:: Fluid, Backend, Frontend
