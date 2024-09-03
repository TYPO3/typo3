.. include:: /Includes.rst.txt

.. _feature-104451-1721646565:

===========================================================
Feature: #104451 - Redis backends support for key prefixing
===========================================================

See :issue:`104451`

Description
===========

It is now possible to add a dedicated key prefix for all invocations of a Redis
cache or session backend. This allows to use the same Redis database for multiple
caches or even for multiple TYPO3 instances if the provided prefix is unique.

Possible use cases are:

* Using Redis caching for multiple caches, if only one Redis database is available
* Pre-fill caches upon deployments using a new prefix (zero downtime deployments)

..  code-block:: php
    :caption: additional.php example for using Redis as session backend

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['BE'] = [
        'backend' => \TYPO3\CMS\Core\Session\Backend\RedisSessionBackend::class,
        'options' => [
            'hostname' => 'redis',
            'database' => '11',
            'compression' => true,
            'keyPrefix' => 'be_sessions_',
        ],
    ];
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['FE'] = [
        'backend' => \TYPO3\CMS\Core\Session\Backend\RedisSessionBackend::class,
        'options' => [
            'hostname' => 'redis',
            'database' => '11',
            'compression' => true,
            'keyPrefix' => 'fe_sessions_',
            'has_anonymous' => true,
        ],
    ];

..  code-block:: php
    :caption: additional.php example for pages cache

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['backend'] = \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['options']['hostname'] = 'redis';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['options']['database'] = 11;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['options']['compression'] = true;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['pages']['options']['keyPrefix'] = 'pages_';

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['rootline']['backend'] = \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['rootline']['options']['hostname'] = 'redis';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['rootline']['options']['database'] = 11;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['rootline']['options']['compression'] = true;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['rootline']['options']['keyPrefix'] = 'rootline_';

Impact
======

The new feature allows to use the same Redis database for multiple caches or even
for multiple TYPO3 instances while having no impact on existing configuration.

..  attention::
    If you start using the same Redis database for multiple caches or
    using the same database also for session storage, make sure any involved
    cache configuration uses **a unique key prefix**.
    If only one of the caches does not use a key prefix, any cache flush
    operation will always flush the whole database, hence also all other caches/sessions.

.. index:: Frontend, LocalConfiguration, ext:core
