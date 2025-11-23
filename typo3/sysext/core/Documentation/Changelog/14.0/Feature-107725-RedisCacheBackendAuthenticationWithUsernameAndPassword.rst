..  include:: /Includes.rst.txt

..  _feature-107725-1760807740:

=============================================================================
Feature: #107725 - Support username for authentication in Redis cache backend
=============================================================================

See :issue:`107725`

Description
===========

Since Redis 6.0, it is possible to authenticate against Redis using both a
username and a password. Prior to this version, authentication was only
possible with a password. With this change, the Redis cache backend in TYPO3
now supports both authentication mechanisms.

You can configure the Redis cache backend as follows:

..  code-block:: php
    :caption: config/system/additional.php

    use TYPO3\CMS\Core\Cache\Backend\RedisBackend;

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['backend'] = RedisBackend::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['options']
        = [
            'defaultLifetime' => 86400,
            'database' => 0,
            'hostname' => 'redis',
            'port' => 6379,
            'username' => 'redis',
            'password' => 'redis',
        ];

Impact
======

The :php-short:`\TYPO3\CMS\Core\Cache\Backend\RedisBackend` now supports
authentication using both a username and a password.

The :php:`password` configuration option is now typed as
:php:`array|string`. Using an array for this configuration option is
deprecated and will be removed in TYPO3 v15.0.

..  index:: LocalConfiguration, ext:core
