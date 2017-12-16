
.. include:: ../../Includes.txt

===============================================================================
Deprecation: #63453 - Deprecate renderMode attribute of FlashMessagesViewHelper
===============================================================================

See :issue:`63453`

Description
===========

Deprecated `renderMode` in favor of a flexible deferred rendering of flash messages in the Fluid template.
This means that flash messages should no longer contain HTML, but the HTML output can and should be adjusted in the
Fluid template.


Impact
======

Using `renderMode` on FlashMessage output will throw a deprecation warning.


Affected Installations
======================

All instances using the renderMode attribute in FlashMessage output.


Migration
=========

Adjust flash messages to contain only plain text and remove the renderMode attribute in the output Templates.

.. code-block:: html

	<f:flashMessages as="flashMessages">
		<ul class="typo3-flashMessages">
			<f:for each="{flashMessages}" as="flashMessage">
				<li class="alert {flashMessage.class}">
					<h4>{flashMessage.title}</h4>
					{flashMessage.message}
				</li>
			</f:for>
		</ul>
	</f:flashMessages>
