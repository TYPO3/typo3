..  include:: /Includes.rst.txt

..  _deprecation-107725-1760807740:

=====================================================================================================
Deprecation: #107725 - Deprecate usage of array in password for authentication in Redis cache backend
=====================================================================================================

See :issue:`107725`

Description
===========

Since Redis 6.0, it is possible to authenticate against Redis using both a
username and a password. Prior to this version, authentication was only
possible with a password.

With this change, TYPO3's Redis cache backend supports username and password
authentication directly. You can now configure the TYPO3 Redis cache backend
as follows:

..  code-block:: php
    :caption: config/system/additional.php

    use TYPO3\CMS\Core\Cache\Backend\RedisBackend;

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['backend']
        = RedisBackend::class;

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

The `password` configuration option of the Redis cache backend is now
typed as :php:`array|string`.

Setting this configuration option with an array is deprecated and will be
removed in TYPO3 v15.0.

Affected installations
======================

All installations using the Redis cache backend and configuring the
`password` option as an array containing both username and password
values are affected.

Migration
=========

Use the dedicated configuration options `username` and `password`
instead of passing an array to `password`.

**Before (deprecated):**

..  code-block:: php
    :caption: config/system/additional.php

    use TYPO3\CMS\Core\Cache\Backend\RedisBackend;

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['backend']
        = RedisBackend::class;

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['options']
        = [
            'defaultLifetime' => 86400,
            'database' => 0,
            'hostname' => 'redis',
            'port' => 6379,
            'password' => [
                'user' => 'redis',
                'pass' => 'redis',
            ],
        ];

**After (recommended):**

..  code-block:: php
    :caption: config/system/additional.php

    use TYPO3\CMS\Core\Cache\Backend\RedisBackend;

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['backend']
        = RedisBackend::class;

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['options']
        = [
            'defaultLifetime' => 86400,
            'database' => 0,
            'hostname' => 'redis',
            'port' => 6379,
            'username' => 'redis',
            'password' => 'redis',
        ];

..  index:: LocalConfiguration, NotScanned, ext:core
