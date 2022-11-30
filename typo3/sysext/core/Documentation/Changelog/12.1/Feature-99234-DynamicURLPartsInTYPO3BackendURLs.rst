.. include:: /Includes.rst.txt

.. _feature-99234-1669840449:

=========================================================
Feature: #99234 - Dynamic URL parts in TYPO3 Backend URLs
=========================================================

See :issue:`99234`

Description
===========

TYPO3's Backend URL routing now uses Symfony's Routing component for resolving
and generating URLs.

This way, it is possible for extension authors to register Backend Routes with
path segments that contain dynamic parts, which are then resolved into a request
attribute called 'routing'.

These routes are defined within the route path as named placeholders.


Impact
======

It is possible to define routes with placeholders in an extensions' :file:'Routes.php':

.. code-block:: php

	return [
	    'my_route' => [
	        'path' => '/rollback-item/{identifier}',
	        'target' => \MyVendor\MyPackage\Controller\RollbackController::class . '::handle',
	    ],
	];


Within the Controller:

.. code-block:: php

	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		$routing = $request->getAttribute('routing');
		$myIdentifier = $routing['identifier'];
		$route = $routing->getRoute();
        ...
	}

.. index:: Backend, PHP-API, ext:backend
