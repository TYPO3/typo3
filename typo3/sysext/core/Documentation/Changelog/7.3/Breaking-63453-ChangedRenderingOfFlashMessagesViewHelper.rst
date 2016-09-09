
.. include:: ../../Includes.txt

===============================================================
Breaking: #63453 - Changed rendering of FlashMessagesViewHelper
===============================================================

See :issue:`63453`

Description
===========

The default (`renderMode="ul"`) rendering output of the `FlashMessagesViewHelper` has been changed.

By default the view helper rendered an unordered list, each list item containing one message.
This output has been adjusted and more markup has been added.


Impact
======

You may see unexpected formatting of flash messages.


Affected Installations
======================

Any template using the `FlashMessagesViewHelper` unless the attribute `renderMode` is set to "div".
Be aware that the `renderMode` attribute has been deprecated.


Migration
=========

Add a custom rendering template for the flash messages, like outlined in the example, to obtain the same output
as before.

.. code-block:: html

	<f:flashMessages as="flashMessages">
		<ul class="myFlashMessages">
			<f:for each="{flashMessages}" as="flashMessage">
				<li>{flashMessage.message}</li>
			</f:for>
		</ul>
	</f:flashMessages>
