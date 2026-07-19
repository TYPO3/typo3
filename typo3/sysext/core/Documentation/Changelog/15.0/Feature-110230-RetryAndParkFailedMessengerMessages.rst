..  include:: /Includes.rst.txt

..  _feature-110230-1784620000:

===========================================================
Feature: #110230 - Retry and park failed Messenger messages
===========================================================

See :issue:`110230`

Description
===========

A message consumed asynchronously by a Symfony Messenger worker
(:bash:`typo3 messenger:consume <transport>`) was lost when its handler failed.
TYPO3 now provides an opt-in failure transport together with a per-transport
retry strategy, and the console commands to inspect, retry and remove the
parked messages.

The failure handling applies only to asynchronous processing by a worker. It
reacts to the :php:`\Symfony\Component\Messenger\Event\WorkerMessageFailedEvent`
of the worker, using the listeners of Symfony Messenger. A synchronous dispatch,
including the :php:`SyncTransport`, is never affected.

Enabling the failure transport
==============================

The failure transport is disabled by default. Name the transport failed messages
should be parked in:

..  code-block:: php
    :caption: config/system/settings.php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = 'failed';

TYPO3 ships a :php:`failed` transport that stores the messages in the table
:sql:`sys_messenger_messages` with the queue name :php:`failed`, so no additional
database table is required.

A failure transport is not consumed by :bash:`messenger:consume`, so a worker
started without a transport name still consumes the :php:`doctrine` transport
only. Parked messages are handled with the :bash:`messenger:failed:*` commands.

Per-transport failure transport
===============================

By default every transport shares the global failure transport. A transport can
use its own failure transport, or opt out of failure handling, with the option
:php:`failure_transport` keyed by the transport name:

..  code-block:: php
    :caption: config/system/settings.php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['failure_transport'] = 'failed';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['transports'] = [
        // failures of the "email" transport go to a dedicated failure transport
        'email' => ['failure_transport' => 'failed_email'],
        // the "reporting" transport opts out of failure handling
        'reporting' => ['failure_transport' => ''],
    ];

A transport without the option uses the global failure transport. An empty value
disables the failure transport for that transport.

A failure transport must be registered as a messenger sender and tagged
:yaml:`messenger.failure_transport`, so that the :bash:`messenger:failed:*`
commands can operate on it:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    services:
      MyVendor\MyExtension\Messenger\FailedEmailTransport:
        class: Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport
        factory: [ '@TYPO3\CMS\Core\Messenger\DoctrineTransportFactory', 'createTransport' ]
        arguments:
          $options:
            queue_name: 'failed_email'
        tags:
          - { name: 'messenger.sender', identifier: 'failed_email' }
          - { name: 'messenger.failure_transport', identifier: 'failed_email' }

To decide the failure transport of a transport at runtime, listen to the new
PSR-14 event :php:`\TYPO3\CMS\Core\Messenger\Event\ResolveFailureTransportEvent`.
It carries the name of the transport the message failed on and the failure
transport resolved from the configuration. Setting :php:`null` disables the
failure transport for that transport:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/RouteImportFailures.php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Messenger\Event\ResolveFailureTransportEvent;

    final class RouteImportFailures
    {
        #[AsEventListener]
        public function __invoke(ResolveFailureTransportEvent $event): void
        {
            if ($event->getReceiverName() === 'import') {
                $event->setFailureTransportName('failed_import');
            }
        }
    }

Retrying before parking
=======================

A transport can retry a failed message a number of times before it is sent to
the failure transport. Configure a retry strategy per transport, keyed by the
transport name:

..  code-block:: php
    :caption: config/system/settings.php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['transports']['doctrine']['retry_strategy'] = [
        'max_retries' => 3,
        'delay' => 1000,    // milliseconds before the first retry
        'multiplier' => 2,  // the delay is multiplied by this for each retry
        'max_delay' => 0,   // maximum delay in milliseconds, 0 = no limit
    ];

Unlike in Symfony applications, a transport without a :php:`retry_strategy` is
not retried, so existing transports keep their behaviour. A failed message is
sent straight to the failure transport, if one is configured.

Console commands
================

Three commands operate on the failure transport:

*   :bash:`messenger:failed:show` lists the parked messages, or shows a single
    message when given its id.
*   :bash:`messenger:failed:retry` handles one or more messages again, by id or
    all pending messages with :bash:`--force`, as received from the transport
    they failed on. Handlers that succeeded before are skipped. A message that
    succeeds is removed from the failure transport. A message that fails again
    is parked again with the new error, and the command exits with a failure
    code.
*   :bash:`messenger:failed:remove` deletes the given messages from the failure
    transport.

None of the commands are offered as scheduler tasks.

Impact
======

Asynchronous message processing no longer loses a message whose handler fails:
the message is retried according to the retry strategy of its transport and, if
it still fails, parked in the failure transport, where it can be inspected,
retried or removed on the command line.

..  index:: CLI, Database, PHP-API, ext:core
