.. include:: /Includes.rst.txt

.. _feature-102586-1701536342:

===========================================================================
Feature: #102586 - Introduce sortable Doctrine DBAL middleware registration
===========================================================================

See :issue:`102586`

Description
===========

TYPO3 v12 introduced the ability to register Doctrine DBAL driver middlewares for connections,
using a simple :php:`'identifier' => MyClass::class,'` configuration schema.

TYPO3 v13 introduces Doctrine DBAL driver middleware registration on a global configuration
level, allow extension authors to register middleware once but using it for all connections.

:ref:`Global driver middlewares <_feature-102496-1700775381` and :ref:`connection driver middlewares <_feature-100089-1677961107>`
are combined for a connection. The simple configuration approach introduced for the
:ref:`connection driver middlewares <_feature-100089-1677961107>` is no longer suitable
for a easy dependency configuration or disabling a global driver middleware by connection.

The way to register and order PSR-15 middlewares has proven to be a reliable way,
and understood by extension authors and integrators.

TYPO3 makes the global and connection driver middleware configuration sortable using the
:ref:`DependencyOrderingService <_feature-67293>` (:php:`\TYPO3\CMS\Core\Service\DependencyOrderingService`)
similar to the PSR-15 middleware stack. Available structure for a middleware configuration is:

..  code-block:: php
    :caption: Basic driver middleware configuration array and PHPStan doc-block definition

    /** @var array{target: string, disabled?: bool, after?: string[], before?: string[]} $middlewareConfiguration */
    $middlewareConfiguration = [
      // target is required
      'target' => 'class fqdn',       // for example MyDriverMiddleware::class
      // disabled can be used to disable a global middleware for a specific
      // connection. This is optional and defaults to `false` if not provided
      'disabled' => false,
      // list of middleware identifiers, the current middleware should be registered after
      'after' => [
        // It's highly advised to define at least the following identifier to ensure that
        // getDatabasePlatform() returns the correct platform instances.
        'typo3/core/custom-platform-driver-middleware',
      ],
      // list of middleware identifiers, the current middleware should be registered before
      'before' => [
        'some-driver-middleware-identifier',
      ],
    ];

    // Register global driver middleware
    $GLOBALS['TYPO3_CONF_VARS']['DB']['globalDriverMiddlewares']['global-driver-middleware-identifier']
      = $middlewareConfiguration;

    // Register connection driver middleware
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['SecondDatabase']['driverMiddlewares']['connection-driver-middleware-identifier']
      = $middlewareConfiguration;

    // Simple disable a global driver middleware for a connection
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['SecondDatabase']['driverMiddlewares']['global-driver-middleware-identifier'] = [
      // to disable a global driver middleware, setting disabled to true for a connection
      // is enough. Repeating target, after and/or before configuration is not required.
      'disabled' => false,
    ];

..  info::

    All custom driver middlewares, `global` or `connection` based, should be
    placed after the `'typo3/core/custom-platform-driver-middleware'` driver
    middleware.

..  tip::

    If `ext:lowlevel` is installed and active, a `Doctrine DBAL Driver Middleware` section
    is provided to view the raw middleware configuration and the ordered middleware for each
    connection.

Impact
======

Using custom driver middlewares allows to enhance the functionality of Doctrine
components for all connections or a specific connection and have control over
the sorting configuration - and it's now also possible to disable global driver
middleware for a specific connection.

.. index:: Database, PHP-API, ext:core
