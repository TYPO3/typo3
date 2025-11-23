..  include:: /Includes.rst.txt

..  _feature-101059-1751729746:

=========================================================================
Feature: #101059 - Allow install tool sessions without shared file system
=========================================================================

See :issue:`101059`

Description
===========

It is now possible to store Install Tool sessions in Redis or configure
the session storage path for file-based Install Tool sessions.

As a shipped session handler, Redis can now be configured via options in
:php:`$GLOBALS['TYPO3_CONF_VARS']` for host, port, database, and authentication.

Example
=======

To configure an alternative session handler for Install Tool sessions, set the
required options in your :file:`settings.php` or :file:`additional.php` file:

..  code-block:: php
    :caption: File-based session handler in config/system/settings.php

    use TYPO3\CMS\Core\Core\Environment;
    use TYPO3\CMS\Install\Service\Session\FileSessionHandler;

    return [
        'BE' => [
            'installToolSessionHandler' => [
                'className' => FileSessionHandler::class,
                'options' => [
                    'sessionPath' => Environment::getVarPath() . '/session',
                ],
            ],
        ],
    ];

..  code-block:: php
    :caption: Redis session handler in config/system/settings.php

    use TYPO3\CMS\Install\Service\Session\RedisSessionHandler;

    return [
        'BE' => [
            'installToolSessionHandler' => [
                'className' => RedisSessionHandler::class,
                'options' => [
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'database' => 0,
                    'authentication' => [
                        'user' => 'redis',
                        'pass' => 'redis',
                    ],
                ],
            ],
        ],
    ];

Impact
======

The default file-based session handling for the Install Tool remains unchanged.
If no alternative session handler for the Install Tool is configured,
the default behavior is used.

Custom session handlers can be created by implementing PHP's
:php:`\SessionHandlerInterface`.

..  index:: Backend, LocalConfiguration, ext:install
