.. include:: /Includes.rst.txt

.. _feature-97700-1672214769:

====================================================================
Feature: #97700 - Adopt Symfony Messenger as a message bus and queue
====================================================================

See :issue:`97700`

Description
===========

This feature provides a basic implementation of a message bus based on the
`Symfony Messenger component <https://symfony.com/doc/current/messenger.html>`__.
For backwards compatibility, the default implementation uses the synchronous
transport. This means that the message bus will behave exactly as before, but it
will be possible to switch to a different (async) transport on a per-project
base. To offer asynchronicity, the feature also provides a transport implementation
based on the Doctrine DBAL messenger transport from Symfony and a basic
implementation of a consumer command.

As an example, the workspace :class:`StageChangeNotification` has been rebuilt as a
message and corresponding handler.

"Everyday" usage - as a developer
---------------------------------

Dispatch a message
~~~~~~~~~~~~~~~~~~

-   Add a PHP class for your message object (arbitrary PHP class)
    (:php:`DemoMessage`)

    ..  code-block:: php

        <?php

        namespace TYPO3\CMS\Queue\Message;

        final class DemoMessage
        {
            public function __construct(public readonly string $content)
            {
            }
        }

-   Inject :php:`\Symfony\Component\Messenger\MessageBusInterface` into your class
-   Call :php:`dispatch()` method with a message as argument

    ..  code-block:: php

        public function __construct(private readonly MessageBusInterface $bus)
        {
        }

        public function yourMethod(): void
        {
            // ...
            $this->bus->dispatch(new DemoMessage('test'));
            // ...
        }

Register a handler
~~~~~~~~~~~~~~~~~~

Use a tag to register a handler. Use before/after to define order.
Define handled message by argument type reflection or by key `message`.

..  code-block:: php

    namespace TYPO3\CMS\Queue\Handler;

    use TYPO3\CMS\Queue\Message\DemoMessage;

    class DemoHandler
    {
        public function __invoke(DemoMessage $message): void
        {
            // do something with $message
        }
    }

..  code-block:: yaml

    TYPO3\CMS\Queue\Handler\DemoHandler:
      tags:
        - name: 'messenger.message_handler'

    TYPO3\CMS\Queue\Handler\DemoHandler2:
      tags:
        - name: 'messenger.message_handler'
          before: 'TYPO3\CMS\Queue\Handler\DemoHandler'

Everyday Usage - as a sysadmin/integrator
-----------------------------------------

By default, the system behaves as before. This means that the message bus
uses the synchronous transport and all messages are handled immediately.
To benefit from the message bus, it is recommended to switch to an asynchronous
transport. Using asynchronous transports increases the resilience of the system
by decoupling external dependencies even further.

The TYPO3 Core currently provides an asynchronous transport based on the
Doctrine DBAL messenger transport. This transport is configured to use the
default TYPO3 database connection. It is pre-configured and can be used
by changing the settings in :file:`config/settings.php`:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing']['*'] = 'doctrine';

This will route all messages to the asynchronous transport.

If you are using the Doctrine transport, make sure to take care of running the
consume command (see below).


Async message handling - The consume command
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Run the command :bash:`./bin/typo3 messenger:consume <receiver-name>` to consume messages.
By default, you should run `./bin/typo3 messenger:consume doctrine`. The command is a
slimmed-down wrapper for the Symfony command `messenger:consume`, it only provides
the basic consumption functionality. As this command is running as a worker,
it is stopped after 1 hour to avoid memory leaks. The command should therefore
be run from a service manager like `systemd` to automatically restart it after
the command exits due to the time limit.

Create a service via :file:`/etc/systemd/system/typo3-message-consumer.service`:

..  code-block:: ini

    [Unit]
    Description=Run the TYPO3 message consumer
    Requires=mariadb.service
    After=mariadb.service

    [Service]
    Type=simple
    User=www-data
    Group=www-data
    ExecStart=/usr/bin/php8.1 /var/www/myproject/vendor/bin/typo3 messenger:consume doctrine --exit-code-on-limit 133
    # Generally restart on error
    Restart=on-failure
    # Restart on exit code 133 (which is returned by the command when limits are reached)
    RestartForceExitStatus=133
    # ..but do not interpret exit code 133 as an error (as it's just a restart request)
    SuccessExitStatus=133

    [Install]
    WantedBy=multi-user.target

The message worker can than be enabled and started via
:bash:`systemctl enable --now typo3-message-consumer`


Advanced Usage
--------------

Configure a custom transport (senders/receivers)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Set up transports in services configuration. To configure one transport per
message, the TYPO3 configuration (:file:`config/settings.php`,
:file:`config/additional.php` on system level or :file:`ext_localconf.php`) is
used. The transport/sender name used in the settings is
resolved to a service that has been tagged with `message.sender` and the
respective identifier.

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger'] = [
        'routing' => [
            // use "messenger.transport.demo" as transport for DemoMessage
            \TYPO3\CMS\Queue\Message\DemoMessage::class => 'demo',
            // use "messenger.transport.default" as transport for all other messages
            '*' => 'default',
        ]
    ];

..  code-block:: yaml

    messenger.transport.demo:
      factory: [ '@TYPO3\CMS\Core\Messenger\DoctrineTransportFactory', 'createTransport' ]
      class: 'Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport'
      arguments:
        $options:
          queue_name: 'demo'
      tags:
        - name: 'messenger.sender'
          identifier: 'demo'
        - name: 'messenger.receiver'
          identifier: 'demo'

    messenger.transport.default:
      factory: [ '@Symfony\Component\Messenger\Transport\InMemory\InMemoryTransportFactory', 'createTransport' ]
      class: 'Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport'
      arguments:
        $dsn: 'in-memory://default'
        $options: [ ]
      tags:
        - name: 'messenger.sender'
          identifier: 'default'
        - name: 'messenger.receiver'
          identifier: 'default'

The TYPO3 Core has been tested with three transports:

-   :php:`\Symfony\Component\Messenger\Transport\Sync\SyncTransport` (default)
-   :php:`\Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport` (using the Doctrine DBAL messenger transport)
-   :php:`\Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport` (for testing)

InMemoryTransport for testing
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

:php:`\Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport` is a
transport that should only be used while testing. See the `SymfonyCasts
tutorial <https://symfonycasts.com/screencast/messenger/test-in-memory>`__
for more details.

..  code-block:: yaml

    messenger.transport.default:
      factory: [ '@Symfony\Component\Messenger\Transport\InMemory\InMemoryTransportFactory', 'createTransport' ]
      class: 'Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport'
      public: true
      arguments:
        $dsn: 'in-memory://default'
        $options: [ ]
      tags:
        - name: 'messenger.sender'
          identifier: 'default'
        - name: 'messenger.receiver'
          identifier: 'default'


Configure a custom middleware
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Set up a middleware in the services configuration. By default,
:php:`\Symfony\Component\Messenger\Middleware\SendMessageMiddleware`
and :php:`\Symfony\Component\Messenger\Middleware\HandleMessageMiddleware`
are registered - see also `Symfony's documentation
<https://symfony.com/doc/current/components/messenger.html#bus>`__.
To add your own message middleware, tag it as :yaml:`messenger.middleware`
and set the order using TYPO3's `before` and `after` ordering mechanism.

..  code-block:: yaml

    Symfony\Component\Messenger\Middleware\SendMessageMiddleware:
      arguments:
        $sendersLocator: '@Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface'
        $eventDispatcher: '@Psr\EventDispatcher\EventDispatcherInterface'
      tags:
        - { name: 'messenger.middleware' }

    Symfony\Component\Messenger\Middleware\HandleMessageMiddleware:
      arguments:
        $handlersLocator: '@Symfony\Component\Messenger\Handler\HandlersLocatorInterface'
      tags:
        - name: 'messenger.middleware'
          after: 'Symfony\Component\Messenger\Middleware\SendMessageMiddleware'


.. index:: PHP-API, ext:core
