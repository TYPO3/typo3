.. include:: /Includes.rst.txt

.. _feature-102496-1700775381:

====================================================================
Feature: #102496 - Introduce global Doctrine DBAL driver middlewares
====================================================================

See :issue:`102496`

Description
===========

Since v3, Doctrine DBAL supports adding custom driver middlewares. These
middlewares act as a decorator around the actual `Driver` component.
Subsequently, the `Connection`, `Statement` and `Result` components can be
decorated as well. These middlewares must implement the
:php:`\Doctrine\DBAL\Driver\Middleware` interface.
A common use case would be a middleware for implementing SQL logging capabilities.

For more information on driver middlewares,
see https://www.doctrine-project.org/projects/doctrine-dbal/en/current/reference/architecture.html.
Furthermore, you can look up the implementation of the
:php:`\TYPO3\CMS\Adminpanel\Log\DoctrineSqlLoggingMiddleware` in ext:adminpanel
as an example.

With :ref:`Feature: #100089 - Introduce Doctrine DBAL v3 driver middlewares <feature-100089-1677961107>` this
has been introduced as a configuration per connection.

Now it's also possible to register global driver middlewares once, which are applied
to all configured connections and then the specific connection middlewares.

See :ref:`sortable Doctrine DBAL middleware registration <feature-102586-1701536342>` feature
changelog for further details about the middleware configuration block.

..  warning::

    Do not remove or disable provided global Core driver middlewares which are
    essential.

Registering a new global driver middleware
==========================================

..  code-block:: php

    use MyVendor\MyExt\Doctrine\Driver\CustomGlobalDriverMiddleware;

    // Register a global middleware
    $GLOBALS['TYPO3_CONF_VARS']['DB']['globalDriverMiddlewares']['my-ext/custom-global-driver-middleware'] = [
      'target' => CustomGlobalDriverMiddleware::class,
      'after' [
        // NOTE: Custom driver middleware should be registered after essential
        //       TYPO3 Core driver middlewares. Use the following identifiers
        //       to ensure that.
        'typo3/core/custom-platform-driver-middleware',
        'typo3/core/custom-pdo-driver-result-middleware',
      ],
    ];

Disable a global middleware for a specific connection
=====================================================

..  code-block:: php

    use MyVendor\MyExt\Doctrine\Driver\CustomGlobalDriverMiddleware;

    // Register a global middleware
    $GLOBALS['TYPO3_CONF_VARS']['DB']['globalDriverMiddlewares']['my-ext/custom-global-driver-middleware'] = [
      'target' => CustomGlobalDriverMiddleware::class,
      'after' [
        // NOTE: Custom driver middleware should be registered after essential
        //       TYPO3 Core driver middlewares. Use the following identifiers
        //       to ensure that.
        'typo3/core/custom-platform-driver-middleware',
        'typo3/core/custom-pdo-driver-result-middleware',
      ],
    ];

    // Disable a global driver middleware for a specific connection
    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['SecondDatabase']['driverMiddlewares']['my-ext/custom-global-driver-middleware']['disabled'] = true;

Impact
======

Using custom global middlewares allows to enhance the functionality of Doctrine
components for all connections.

.. index:: Database, PHP-API, ext:core
