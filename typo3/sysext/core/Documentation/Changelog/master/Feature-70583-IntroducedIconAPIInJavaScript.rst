===================================================
Feature: #70583 - Introduced Icon API in JavaScript
===================================================

Description
===========

A JavaScript-based icon API based on the PHP API has been introduced. The methods ``getIcon()``
and ``getIcons()`` can be called in an RequireJS module.

When imported in a RequireJS module, a developer can fetch icons via JavaScript with the same parameters as in PHP.
The methods ``getIcon()`` and ``getIcons()`` return ``Promise`` objects.

Importing
=========

.. code-block:: javascript

	define(['jquery', 'TYPO3/CMS/Backend/Icons'], function($, Icons) {
	});


Get icons
=========

A single icon can be fetched by ``getIcon()`` which takes four parameters:

.. container:: table-row

   identifier
         The icon identifier.

   size
         The size of the icon. Please use the properties of the ``Icons.sizes`` object.

   overlayIdentifier
         An overlay identifier rendered on the icon.

   state
         The state of the icon. Please use the properties of the ``Icons.states`` object.


Multiple icons can be fetched by ``getIcons()``. This function takes a multidimensional array as parameter,
holding the parameters used by ``getIcon()`` for each icon.

To use the fetched icons, chain the ``done()`` method to the promise.

Examples
--------

.. code-block:: javascript

	// Get a single icon
	Icons.getIcon('spinner-circle-light', Icons.sizes.small).done(function(icons) {
		$toolbarItemIcon.replaceWith(icons['spinner-circle-light']);
	});

	// Get multiple icons
	Icons.getIcons([
		['apps-filetree-folder-default', Icons.sizes.large],
		['actions-edit-delete', Icons.sizes.small, null, Icons.states.disabled],
		['actions-system-cache-clear-impact-medium']
	]).done(function(icons) {
		// icons['apps-filetree-folder-default']
		// icons['actions-edit-delete']
		// icons['actions-system-cache-clear-impact-medium']
	});
