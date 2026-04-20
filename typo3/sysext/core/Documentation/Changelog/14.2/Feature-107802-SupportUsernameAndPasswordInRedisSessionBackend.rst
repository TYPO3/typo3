..  include:: /Includes.rst.txt

..  _feature-107802-1770827507:

=========================================================================
Feature: #107802 - Support username and password in Redis session backend
=========================================================================

See :issue:`107802`

Description
===========

Since Redis 6.0, it is possible to authenticate against Redis using
both a username and a password. Before that, authentication was possible
by password only. This change means the TYPO3 Redis session backend
can be configured as follows:

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
        ],
    ];

Impact
======

The `password` configuration option of the Redis session backend is now
typed as `array|string`. Setting this configuration option to an array is
deprecated and will be removed in TYPO3 v15.0.

..  index:: LocalConfiguration, ext:core
