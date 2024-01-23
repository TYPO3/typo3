.. include:: /Includes.rst.txt

.. _deprecation-102586-1701536568:

========================================================================================
Deprecation: #102586 - Deprecate simple string connection driver middleware registration
========================================================================================

See :issue:`102586`

Description
===========

Using the simple :php:`'identifier' => MyClass::class,'` configuration schema to register
Doctrine DBAL middlewares for connection is now deprecated in favour of using a sortable
registration configuration similar to the PSR-15 middleware registration.

Impact
======

Connection driver middleware registration using a simple string will emit a corresponding
message to the deprecation log since TYPO3 v13, but converting it on-the-fly to a valid
array configuration.


Affected installations
======================

TYPO3 instances using third-party extension providing custom Doctrine DBAL driver
middlewares and having them registered for one or more connections will emit a
deprecation message since TYPO3 v13 and either an exception with TYPO3 v14 or
an PHP type error.

Migration
=========

Simple driver middleware registration, for example

..  code-block:: php

    use MyVendor\MyExt\Doctrine\Driver\MyDriverMiddlewareClass;

    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['SecondDatabase']['driverMiddlewares']['driver-middleware-identifier']
        = MyDriverMiddlewareClass::class;

needs to be converted to

..  code-block:: php

    use MyVendor\MyExt\Doctrine\Driver\MyDriverMiddlewareClass;

    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['SecondDatabase']['driverMiddlewares']['driver-middleware-identifier'] = [
      'target' => MyDriverMiddlewareClass::class,
      'after' => [
        'typo3/core/custom-platform-driver-middleware',
      ],
    ];

Registration for driver middlewares for TYPO3 v12 and v13
---------------------------------------------------------

Extension authors providing dual Core support with one extension version can use the
:php:`Typo3Version` class to provide the configuration suitable for the Core version
and avoiding the deprecation notice:

..  code-block:: php

    use TYPO3\CMS\Core\Information\Typo3Version;
    use MyVendor\MyExt\Doctrine\Driver\MyDriverMiddlewareClass;

    $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['SecondDatabase']['driverMiddlewares']['driver-middleware-identifier']
        = ((new Typo3Version)->getMajorVersion() < 13)
            ? MyDriverMiddlewareClass::class
            : [
              'target' => MyDriverMiddlewareClass::class,
              'after' => [
                'typo3/core/custom-platform-driver-middleware',
              ],
            ];

.. index:: Database, PHP-API, NotScanned, ext:core
