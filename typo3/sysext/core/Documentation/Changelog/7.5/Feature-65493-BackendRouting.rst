
.. include:: ../../Includes.txt

=========================================
Feature: #58621 - Unified Backend Routing
=========================================

See :issue:`58621`

Description
===========

A new Routing component has been added to the TYPO3 Backend which handles addressing different calls / modules inside TYPO3.

A **Route** is the smallest entity consisting of a path (e.g. "/records/edit/") as well as an identifier for addressing
the route, and the information about how to dispatch the route to a PHP controller.

A **Route** can be a module, wizard or any page inside the TYPO3 Backend. The Router contains the public API for matching
paths to fetch a Route and is resolved inside the RequestHandler of the Backend.

The entry point for Routes is `typo3/index.php?route=myroute&token=....`. The main RequestHandler for all Backend requests
detects if a route parameter from the server is given and uses this as the route identifier and then resolves to a
controller defined inside the Route.

Routes are defined inside the file "Configuration/Backend/Routes.php" of any extension.

Example of a Configuration/Backend/Routes.php file:

.. code-block:: php

	return [
		'myRouteIdentifier' => [
			'path' => '/document/edit',
			'controller' => Acme\MyExtension\Controller\MyExampleController::class . '::methodToCall'
		]
	];

The called method in the controller to be called receives a PSR-7 compliant Request object and a PSR-7 Response object, and has to return a PSR-7 Response object.
The UriBuilder generates any kind of URL for the Backend, may it be a module, a typical route or an AJAX call. The
UriBuilder returns a PSR-7-conform Uri object that can be casted to string when needed.

Usage:

.. code-block:: php

	$uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
	$uri = $uriBuilder->buildUriFromRoute('myRouteIdentifier', array('foo' => 'bar'));

See http://wiki.typo3.org/Blueprints/BackendRouting for more details.

Impact
======

Handling of existing modules works the same as before and fully transparent. Any existing registration of entrypoints
can be moved to the new registration file in Configuration/Backend/Routes.php.


.. index:: PHP-API, Backend
