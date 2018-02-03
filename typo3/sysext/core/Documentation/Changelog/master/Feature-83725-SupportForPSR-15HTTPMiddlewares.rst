.. include:: ../../Includes.txt

=====================================================
Feature: #83725 - Support for PSR-15 HTTP middlewares
=====================================================

See :issue:`83725`

Description
===========

Support for PSR-15 style HTTP middlewares has been added for frontend and backend requests.

PSR-15 implements middlewares (any kind of PHP functionality) which act as layers before the actual
request handlers do their work. Any layer can be added at any point of the request to enrich / exchange a
HTTP request or response object (PSR-7), to add functionality or do early returns of a different Response object
(Access denied to a specific request).

Basic examples of middleware are layers for authentication or security, or handling of Exceptions (like
TYPO3's `PageNotFoundException`) to produce HTTP response objects.

Adding PSR-15 to TYPO3 allows to restructure TYPO3's existing PHP classes into smaller chunks, while giving
Site developers the possibility to add a layer at a specific place (via TYPO3's dependency ordering).

Middlewares in TYPO3 are added into Middleware stacks as not every middleware needs to be called for every HTTP request.
Currently TYPO3 supports a generic "frontend" stack and a "backend" stack, for any TYPO3 Frontend requests or
TYPO3 Backend requests. These stacks are then processed before the actual Request Handler
(implementing PSR-15 RequestHandlerInterface) handles the actual logic. The Request Handler produces a PSR-7 Response
object which is then sent back through all middlewares of a stack.

Impact
======

To add a middleware to the "frontend" or backend middleware stack, create the
:file:`Configuration/RequestMiddlewares.php` in the respective extension:

.. code-block:: php

	return [
		// stack name: currently 'frontend' or 'backend'
		'frontend' => [
			'middleware-identifier' => [
				'target' => \ACME\Ext\Middleware::class,
				'description' => '',
				'before' => [
					'another-middleware-identifier',
				],
				'after' => [
					'yet-another-middleware-identifier',
				],
			]
		]
	];


.. index:: Backend, Frontend, PHP-API, NotScanned
