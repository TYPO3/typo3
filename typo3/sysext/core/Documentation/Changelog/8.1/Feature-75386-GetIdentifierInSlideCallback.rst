
.. include:: /Includes.rst.txt

==================================================
Feature: #75386 - Get identifier in slide callback
==================================================

See :issue:`75386`

Description
===========

The callback of :js:`Wizard.addSlide()` now has a new parameter `identifier`.


Impact
======

The identifier is passed to the callback function of :js:`Wizard.addSlide()`.

Example code:

.. code-block:: javascript

	Wizard.addSlide('my-identifier', 'Foobar', '', Severity.info, function($slide, settings, identifier) {
		console.log(identifier); // my-identifier
	});

.. index:: JavaScript, Backend
