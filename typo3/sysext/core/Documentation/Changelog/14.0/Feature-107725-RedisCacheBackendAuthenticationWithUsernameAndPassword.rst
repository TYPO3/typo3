..  include:: /Includes.rst.txt

..  _feature-107725-1760807740:

=============================================================================
Feature: #107725 - Support username for authentication in Redis cache backend
=============================================================================

See :issue:`107725`

Description
===========

Since Redis 6.0, it is possible to authenticate against Redis using both a username and
a password. Prior to this version, authentication was only possible with a password.
With this patch, you can now configure the TYPO3 Redis cache backend as follows:

..  code-block:: php
    :caption: config/system/additional.php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['backend']
        = \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class;
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

The "password" configuration option of the Redis cache backend is now typed as a
`array|string`. Setting this configuration option with an array is deprecated and
will be removed in 15.0.

..  index:: LocalConfiguration, ext:core
