.. include:: ../../Includes.txt

=====================================================
Feature: #70316 - Introduce Session Storage Framework
=====================================================

See :issue:`70316`

Description
===========

A new session storage framework has been introduced. The goal of this framework is to create interoperability
between different session storages (called "backends") like database, file storage, Redis, etc.


Impact
======

An integrator may configure session backends based on :php:`TYPO3_MODE`, which is either `BE` or `FE`.

The following session backends are available by default:

- :php:`\TYPO3\CMS\Core\Session\Backend\DatabaseSessionBackend`
- :php:`\TYPO3\CMS\Core\Session\Backend\RedisSessionBackend`

The default session backend for `BE` and `FE` is :php:`DatabaseSessionBackend` with `table` set to `fe_sessions` and `be_sessions` respectively.

The configuration of the backend for each :php:`TYPO3_MODE` is stored within `SYS/session`:

.. code-block:: php

    'SYS' => [
        'session' => [
            'BE' => [
                'backend' => \TYPO3\CMS\Core\Session\Backend\RedisSessionBackend::class,
                'options' => [
                    'hostname' => 'localhost',
                    'database' => 2
                ]
            ],
        ],
    ],

The :php:`DatabaseSessionBackend` requires a `table` as option. If the backend is used to holds non-authenticated
sessions (default for `FE`), the `has_anonymous` option must be set to true.

The :php:`RedisSessionBackend` requires a running PHP redis module (PHP extension "redis") and a running redis service.
By default, a connection will be made to `hostname` 127.0.0.1 and `port` 6379. You may also specify a `database`
number which to store the sessions in (default database is 0) and a `password` for the connection.

A developer may implement a custom session backend. To achieve this, the interface
:php:`\TYPO3\CMS\Core\Session\Backend\SessionBackendInterface` has to be implemented.

.. index:: PHP-API
