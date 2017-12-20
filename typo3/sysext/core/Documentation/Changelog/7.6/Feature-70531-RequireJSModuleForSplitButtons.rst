
.. include:: ../../Includes.txt

====================================================
Feature: #70531 - RequireJS module for split buttons
====================================================

See :issue:`70531`

Description
===========

A RequireJS module for split buttons has been added. The module can be used in another RequireJS modules to
add callbacks being executed before the submit takes place. As the callback receives the click event,
the submit can be modified, e.g aborting the submit.


Impact
======

To use the `SplitButtons` module, include it in your own RequireJS module:

.. code-block:: javascript

	define('Vendor/Ext/Module', ['TYPO3/CMS/Backend/SplitButtons'], function(SplitButtons) {
		// Your code...
	});


Callbacks will be added by calling `SplitButtons.addPreSubmitCallback`:

.. code-block:: javascript

	SplitButtons.addPreSubmitCallback(function(e) {
		// Code being executed as callback before submit
	});


.. index:: JavaScript, Backend
