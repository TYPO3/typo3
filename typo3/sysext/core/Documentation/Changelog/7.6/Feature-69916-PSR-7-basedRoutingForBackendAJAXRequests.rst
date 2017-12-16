
.. include:: ../../Includes.txt

===============================================================
Feature: #69916 - PSR-7-based Routing for Backend AJAX Requests
===============================================================

See :issue:`69916`

Description
===========

Support for PSR-7-based Routing for Backend AJAX requests has been added.


Impact
======

To add a route for an AJAX request, create the :file:`Configuration/Backend/AjaxRoutes.php` of your extension:

.. code-block:: php

	return [
		// Does something
		'unique_route_name' => [
			'path' => '/toolcollection/some-action',
			'target' => \ACME\Controller\SomeController::class . '::myAction',
		]
	];

The unique_route_name (route identifier) parameter acts as the previously known key to
call `BackendUtility::getAjaxUrl()` passed as parameter to the action refers to the route path,
**not** to the route identifier itself. AJAX handlers configured in :file:`AjaxRoutes.php` are **not** compatible
with definitions in :file:`ext_localconf.php` registered by `ExtensionManagementUtility::registerAjaxHandler()`
due to different method signatures in the target actions, using PSR-7.

The route identifier is used in `BackendUtility::getAjaxUrl()` as `$ajaxIdentifier` and as key in the global
`TYPO3.settings.ajaxUrls` JavaScript object.
