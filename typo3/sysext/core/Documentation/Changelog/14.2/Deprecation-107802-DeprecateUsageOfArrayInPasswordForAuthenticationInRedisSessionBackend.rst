..  include:: /Includes.rst.txt

..  _deprecation-107802-1770827443:

=======================================================================================================
Deprecation: #107802 - Deprecate usage of array in password for authentication in Redis session backend
=======================================================================================================

See :issue:`107802`

Description
===========

Since Redis 6.0 it is possible to authenticate against Redis using both a username and
a password. Prior to this version, authentication was only possible via password. With
this patch, you can configure the TYPO3 Redis session backend as follows:

..  code-block:: php
    :caption: config/system/additional.php

    use TYPO3\CMS\Core\Session\Backend\RedisSessionBackend;

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['BE'] = [
        'backend' => RedisSessionBackend::class,
        'options' => [
            'database' => 0,
            'hostname' => 'redis',
            'port' => 6379,
            'username' => 'redis',
            'password' => 'redis',
        ]
    ];


Impact
======

The "password" configuration option of the Redis session backend is now typed as
:php:`array|string`. Setting this configuration option with an array is deprecated
and will be removed in 15.0.


Affected installations
======================

All installations using a Redis session backend and using the `password` configuration
option to pass an array with a username and password to it.


Migration
=========

Use the configuration options `username` and `password`.

**Before:**

..  code-block:: php
    :caption: config/system/additional.php

    use TYPO3\CMS\Core\Session\Backend\RedisSessionBackend;

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['BE'] = [
        'backend' => RedisSessionBackend::class,
        'options' => [
            'database' => 0,
            'hostname' => 'redis',
            'port' => 6379,
            'username' => 'redis',
            'password' =>[
                'user' => 'redis',
                'pass' => 'redis'
            ]
        ]
    ];

**After:**

..  code-block:: php
    :caption: config/system/additional.php

    use TYPO3\CMS\Core\Session\Backend\RedisSessionBackend;

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['BE'] = [
        'backend' => RedisSessionBackend::class,
        'options' => [
            'database' => 0,
            'hostname' => 'redis',
            'port' => 6379,
            'username' => 'redis',
            'password' => 'redis',
        ]
    ];

..  index:: LocalConfiguration, NotScanned, ext:core
