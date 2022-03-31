.. include:: /Includes.rst.txt

=====================================================
Feature: #83725 - Support for PSR-15 HTTP middlewares
=====================================================

See :issue:`83725`

Description
===========

Support for PSR-15 style HTTP middlewares has been added for frontend and backend requests.

PSR-15 style middlewares are intended to be used to move common request and response processing away from
the application layer into (possibly reusable) components.
Middlewares are concentric layers surrounding other middlewares (so called inner middlewares) or request handlers;
that means they can perform pre- and postprocessing of request and response objects (PSR-7). They allow to enrich or
exchange PSR-7 objects in order to add functionality or to perform early returns (without invoking the core application).

Common middleware usecases are layers for authentication, authorization, security enforcement, or the conversion of
exceptions (like TYPO3's `PageNotFoundException`) into HTTP response objects.

Adding PSR-15 to TYPO3 allows to restructure TYPO3's existing PHP classes into smaller chunks, while giving developers
the possibility to add own middlewares at a specific position in the middleware chain (via TYPO3's dependency ordering).

Middlewares in TYPO3 are added into middleware stacks; not every middleware needs to be called for every HTTP request.
Currently TYPO3 supports a generic "frontend" and a "backend" stack; they're run for any TYPO3 Frontend or TYPO3 Backend
request respectively. These stacks are processed before the actual Request Handler (which implements the PSR-15
RequestHandlerInterface) handles the application logic. The Request Handler produces a PSR-7 Response object which is
propagated back through all middlewares of the stack.

Impact
======

To add a middleware to the "frontend" or "backend" middleware stack, create the
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

If extensions need to shut down or substitute existing middlewares with an own solution, they can
disable an existing middleware by adding the following code in :file:`Configuration/RequestMiddlewares.php`: of their
extension.

.. code-block:: php

    return [
        'frontend' => [
            'middleware-identifier' => [
                'disabled' => true,
            ],
        ],
    ];

.. index:: Backend, Frontend, PHP-API
