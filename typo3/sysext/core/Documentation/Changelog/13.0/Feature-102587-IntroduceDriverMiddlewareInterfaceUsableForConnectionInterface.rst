.. include:: /Includes.rst.txt

.. _feature-102587-1701564155:

=====================================================================================
Feature: #102587 - Introduce driver middleware interface UsableForConnectionInterface
=====================================================================================

See :issue:`102587`

Description
===========

Since v3, Doctrine DBAL supports adding custom driver middlewares. These
middlewares act as a decorator around the actual `Driver` component.
Subsequently, the `Connection`, `Statement` and `Result` components can be
decorated as well. These middlewares must implement the
:php:`\Doctrine\DBAL\Driver\Middleware` interface.

:ref:`Global driver middlewares <feature-102496-1700775381>` and :ref:`connection driver middlewares <feature-100089-1677961107>`
are available and configuration has been enhanced with the :ref:`DependencyOrderingService <feature-67293>`.

That means, that Doctrine DBAL driver middlewares can be registered globally for
all connections or for specific connections. Due to the nature of the decorator
pattern it may become hard to determine for specific configuration or drivers,
if a middleware needs only be executed for a subset, for example only specific
drivers.

TYPO3 now provides a custom :php:`\TYPO3\CMS\Core\Database\Middleware\UsableForConnectionInterface`
driver middleware interface which requires the implementation of the method

..  code-block:: php

    public function canBeUsedForConnection(
        string $identifier,
        array $connectionParams
    ): bool {}

This allows to decide if a middleware should be used for specific connection,
either based on the :php:`$connectionName` or the :php:`$connectionParams`,
for example the concrete :php:`$connectionParams['driver']`.

..  note::

    Real use cases to use this interface should be rare edge cases, usually
    a driver middleware should only be configured on a connection where is
    needed - or does not harm if used for all connection types as global
    driver middleware.

Custom driver middleware example using the interface
----------------------------------------------------

..  code-block:: php
    :caption: my_extension/Classes/DoctrineDBAL/CustomDriver.php (driver decorator)

    namespace MyVendor\MyExt\DoctrineDBAL;

    use Doctrine\DBAL\Driver\Connection as DriverConnection;
    // Using the abstract class minimize the methods to implement and therefore
    // reduces a lot of boilerplate code. Override only methods needed to be
    // customized.
    use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

    final class CustomDriver extends AbstractDriverMiddleware
    {
      public function connect(#[\SensitiveParameter] array $params): DriverConnection
      {
        $connection = parent::connect($params);

        // @todo Do something custom on connect, for example wrapping the driver
        //       connection class or executing some queries on connect.

        return $connection;
      }
    }

..  code-block:: php
    :caption: my_extension/Classes/DoctrineDBAL/CustomMiddleware.php (driver middleware)

    namespace MyVendor\MyExt\DoctrineDBAL;

    use Doctrine\DBAL\Driver as DoctrineDriverInterface;
    use MyVendor\MyExt\DoctrineDBAL\CustomDriver as MyCustomDriver;
    use TYPO3\CMS\Core\Database\Middleware\UsableForConnectionInterface;

    final class CustomMiddleware implements UsableForConnectionInterface
    {
      public function wrap(DoctrineDriverInterface $driver): DoctrineDriverInterface
      {
        // Wrap the original or already wrapped driver with our custom driver
        // decoration class to provide additional features.
        return new MyCustomDriver($driver);
      }

      public function canBeUsedForConnection(
        string $identifier,
        array $connectionParams
      ): bool {
         // Only use this driver middleware, if the configured connection driver
         // is 'pdo_sqlite' (sqlite using php-ext PDO).
         return ($connectionParams['driver'] ?? '') === 'pdo_sqlite';
      }
    }

..  code-block:: php
    :caption: my_extension/ext_localconf.php (Register custom driver middleware)

    use MyVendor\MyExt\DoctrineDBAL\CustomMiddleware;

    $middlewareConfiguration = [
      'target' => CustomMiddleware::class,
      'after' => [
        // NOTE: Custom driver middleware should be registered after essential
        //       TYPO3 Core driver middlewares. Use the following identifiers
        //       to ensure that.
        'typo3/core/custom-platform-driver-middleware',
        'typo3/core/custom-pdo-driver-result-middleware',
      ],
    ];

    // Register middleware globally, to include it for all connection which
    // uses the 'pdo_sqlite' driver.
    $GLOBALS['TYPO3_CONF_VARS']['DB']['globalDriverMiddlewares']['myvendor/myext/custom-pdosqlite-driver-middleware']
      = $middlewareConfiguration;

Impact
======

Extension author can provide conditional-based Doctrine driver middlewares by
implementing the :php:`\TYPO3\CMS\Core\Database\Middleware\UsableForConnectionInterface`
along with the :php:`canBeUsedForConnection()` method.

.. index:: Database, PHP-API, ext:core
