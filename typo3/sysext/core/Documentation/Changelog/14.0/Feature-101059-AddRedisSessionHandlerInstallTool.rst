..  include:: /Includes.rst.txt

..  _feature-101059-1751729746:

=========================================================================
Feature: #101059 - Allow install tool sessions without shared file system
=========================================================================

See :issue:`101059`

Description
===========

It is now possible to store Install Tool sessions in Redis or to configure
the session storage path for file-based Install Tool sessions.

As a shipped session handler, Redis can now be configured via options
in TYPO3_CONF_VARS for host, port, database and authentication.

Example
=======

To configure an alternative session handler for Install Tool sessions,
set the needed options in your settings.php or additional.php file:

..  code-block:: php
    :caption: File-based session handler in config/system/settings.php

    return [
        'BE' => [
            'installToolSessionHandler' => [
                'className' => \TYPO3\CMS\Install\Service\Session\FileSessionHandler::class,
                'options' => [
                    'sessionPath' => \TYPO3\CMS\Core\Core\Environment::getVarPath() . '/session',
                ]
            ]
        ]
    ];

..  code-block:: php
    :caption: Redis session handler in config/system/settings.php

    return [
        'BE' => [
            'installToolSessionHandler' => [
                'className' => \TYPO3\CMS\Install\Service\Session\RedisSessionHandler::class,
                'options' => [
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'database' => 0,
                    'authentication' => [
                        'user' => 'redis',
                        'pass' => 'redis'
                    ]
                ]
            ]
        ]
    ];

Impact
======

The default file-based session handling for the Install Tool remains unchanged.
If an alternative session handler for the Install Tool is not configured,
the default behavior will be used.

Custom session handlers can be implemented by implementing PHP's own
:php:`\SessionHandlerInterface`.

..  index:: Backend, LocalConfiguration, ext:install
