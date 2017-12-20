
.. include:: ../../Includes.txt

===================================================
Feature: #70583 - Introduced Icon API in JavaScript
===================================================

See :issue:`70583`

Description
===========

A JavaScript-based icon API based on the PHP API has been introduced. The methods `getIcon()`
and `getIcons()` can be called in a RequireJS module.

When imported in a RequireJS module, a developer can fetch icons via JavaScript with the same parameters as in PHP.
The methods `getIcon()` and `getIcons()` return `Promise` objects.

Importing
=========

.. code-block:: javascript

	define(['jquery', 'TYPO3/CMS/Backend/Icons'], function($, Icons) {
	});


Get icons
=========

A single icon can be fetched by `getIcon()` which takes four parameters:

.. container:: table-row

   identifier
         The icon identifier.

   size
         The size of the icon. Please use the properties of the `Icons.sizes` object.

   overlayIdentifier
         An overlay identifier rendered on the icon.

   state
         The state of the icon. Please use the properties of the `Icons.states` object.


To use the fetched icons, chain the `done()` method to the promise.

Examples
--------

.. code-block:: javascript

	// Get a single icon
	Icons.getIcon('spinner-circle-light', Icons.sizes.small).done(function(spinner) {
		$toolbarItemIcon.replaceWith(spinner);
	});


.. index:: Backend, JavaScript
