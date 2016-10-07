
.. include:: ../../Includes.txt

============================================================
Feature: #73720 - Trigger event after modal window dismissed
============================================================

See :issue:`73720`

Description
===========

A new event `modal-destroyed` has been added that will be triggered after a modal window closed.


Impact
======

Bind to the event `modal-destroyed` to achieve custom actions after the modal
has been dismissed.

Example code:

.. code-block:: javascript

	var $modal = Modal.confirm(); // stub code
	$modal.on('modal-destroyed', function() {
		// Reset the selection
		$someCombobox.val('');
	});

.. index:: Backend, JavaScript
