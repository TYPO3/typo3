..  include:: /Includes.rst.txt

..  _feature-110227-1784474977:

=====================================================================================
Feature: #110227 - Database connection maintenance for long-running Messenger workers
=====================================================================================

See :issue:`110227`

Description
===========

Long-running Symfony Messenger workers started via
:bash:`typo3 messenger:consume <transport>` keep a PHP process - and its database
connections - alive across many messages. A connection that stays idle between two
messages can be dropped by the database server (for example MySQL's
:sql:`wait_timeout`), so the next message handled by the worker fails with errors
such as "MySQL server has gone away".

Two messenger middlewares now maintain the database connections of a worker:

*   :php:`\TYPO3\CMS\Core\Messenger\Middleware\DoctrinePingConnectionMiddleware`
    pings each open connection with a lightweight dummy select *before* a message
    is handled and transparently closes and reconnects a connection that turned
    out to be stale.
*   :php:`\TYPO3\CMS\Core\Messenger\Middleware\DoctrineCloseConnectionMiddleware`
    closes the open connections *after* a message has been handled - even when
    handling throws - so the next message starts with a fresh connection.

The middlewares are TYPO3-native and operate on
:php:`\TYPO3\CMS\Core\Database\ConnectionPool`, so - unlike the equivalents in
:composer:`symfony/doctrine-bridge` - they do not require Doctrine ORM or a
:php:`ManagerRegistry`.

Both middlewares are registered and active by default. No configuration is needed.

Only asynchronous handling is affected
======================================

The middlewares act exclusively on messages consumed asynchronously by a worker,
that is on envelopes carrying a
:php:`\Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp`. A synchronous
dispatch - including the :php:`SyncTransport` - is passed through untouched, so
regular request handling never pings or closes database connections.

To avoid opening connections needlessly, only connections that are already open in
the pool are pinged or closed; configured but unused connections are left alone.

Embedded SQLite connections are never pinged or closed: they are not server-based
and cannot go stale, and closing an in-memory SQLite database would discard all
its data.

Excluding individual connections
================================

Both middlewares dispatch a cancellable PSR-14 event per connection, allowing
listeners to skip the ping or the close for a specific connection - for example to
keep a dedicated connection open across messages:

*   :php:`\TYPO3\CMS\Core\Messenger\Event\BeforeMessengerDoctrinePingEvent`
*   :php:`\TYPO3\CMS\Core\Messenger\Event\BeforeMessengerDoctrineCloseEvent`

Each event exposes the affected :php:`getConnectionName()` and a :php:`skip()`
method (with :php:`shouldSkip()`), and stops propagation once skipped:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Messenger\Event\BeforeMessengerDoctrineCloseEvent;

    final class KeepReportingConnectionOpen
    {
        #[AsEventListener]
        public function __invoke(BeforeMessengerDoctrineCloseEvent $event): void
        {
            if ($event->getConnectionName() === 'Reporting') {
                // Keep the "Reporting" connection open across messages.
                $event->skip();
            }
        }
    }

Impact
======

Database connections used by a :bash:`messenger:consume` worker are now kept alive
(reconnected when stale) and released between messages automatically, removing a
common cause of "server has gone away" failures in long-running workers. Projects
that must keep a specific connection open across messages can opt out per
connection via the two events.

Providing this feature on older TYPO3 versions
==============================================

The two middlewares and their events are self-contained and can be provided on
an older TYPO3 version that does not ship them yet. There is one caveat: the
middlewares rely on the new method
:php:`\TYPO3\CMS\Core\Database\ConnectionPool::getOpenConnectionNames()`, which
is not available on older versions and - being a public API method on a core
class - cannot be added from an extension. It has to be either patched into the
core (Composer patch) or emulated in the copied middlewares via reflection.

Copy the middlewares into a local extension
-------------------------------------------

Copy the four classes into an extension, keeping their logic:

*   :php:`\TYPO3\CMS\Core\Messenger\Middleware\DoctrinePingConnectionMiddleware`
*   :php:`\TYPO3\CMS\Core\Messenger\Middleware\DoctrineCloseConnectionMiddleware`
*   :php:`\TYPO3\CMS\Core\Messenger\Event\BeforeMessengerDoctrinePingEvent`
*   :php:`\TYPO3\CMS\Core\Messenger\Event\BeforeMessengerDoctrineCloseEvent`

Register the two copied middlewares in the extension's
:file:`Configuration/Services.yaml` with the same ordering (close outermost,
ping innermost), referencing the extension classes and the always-present
Symfony core middlewares:

..  code-block:: yaml

    services:
      MyVendor\MyExtension\Messenger\Middleware\DoctrineCloseConnectionMiddleware:
        tags:
          - name: 'messenger.middleware'
            after: 'Symfony\Component\Messenger\Middleware\SendMessageMiddleware'
            before: 'MyVendor\MyExtension\Messenger\Middleware\DoctrinePingConnectionMiddleware'

      MyVendor\MyExtension\Messenger\Middleware\DoctrinePingConnectionMiddleware:
        tags:
          - name: 'messenger.middleware'
            after: 'MyVendor\MyExtension\Messenger\Middleware\DoctrineCloseConnectionMiddleware'
            before: 'Symfony\Component\Messenger\Middleware\HandleMessageMiddleware'

In the copied middlewares, replace the call to
:php:`$this->connectionPool->getOpenConnectionNames()` with a private helper
that reads the protected :php:`$connections` property of the
:php:`ConnectionPool` via reflection (no :php:`setAccessible()` call is needed on
PHP 8.1 and newer):

..  code-block:: php

    use TYPO3\CMS\Core\Database\Connection;
    use TYPO3\CMS\Core\Database\ConnectionPool;

    /**
     * @return list<string>
     */
    private function getOpenConnectionNames(): array
    {
        $property = new \ReflectionProperty(ConnectionPool::class, 'connections');
        /** @var array<string, Connection> $connections */
        $connections = $property->getValue($this->connectionPool);

        return array_values(array_filter(
            array_keys($connections),
            static fn(string $connectionName): bool => $connectionName !== '',
        ));
    }

Because the middlewares only ever act on connections already open in the pool
(and skip embedded SQLite connections), reading the currently instantiated
connections is exactly what the core method returns.

Backport the change via a Composer patch
----------------------------------------

Alternatively, apply the change to :file:`typo3/cms-core` directly using a
Composer patch tool such as `cweagans/composer-patches
<https://github.com/cweagans/composer-patches>`__:

..  code-block:: json

    {
        "require": {
            "cweagans/composer-patches": "^1.7"
        },
        "extra": {
            "patches": {
                "typo3/cms-core": {
                    "Messenger Doctrine connection maintenance": "patches/messenger-doctrine-maintenance.patch"
                }
            }
        }
    }

The patch file *should* contain the new
:php:`ConnectionPool::getOpenConnectionNames()` method, the two middlewares and
the two events, and register the middlewares in
:file:`typo3/sysext/core/Configuration/Services.yaml`. When the method is added
this way, the reflection helper above is not needed and the copied middlewares
can call :php:`getOpenConnectionNames()` directly.

..  note::

    Due to the monorepo to composer package splitting a patch requires adoption
    of the paths and stripping out changes in :file:`Tests/` folders.

    See `Applying Core patches <https://docs.typo3.org/permalink/t3coreapi:applying-core-patches>`_
    for further details on how to create and apply Composer patches for TYPO3.

..  index:: CLI, Database, PHP-API, ext:core
