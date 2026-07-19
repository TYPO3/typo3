..  include:: /Includes.rst.txt

..  _important-107032-1784329900:

==============================================================================
Important: #107032 - AsMessageHandler "fromTransport" property is now honoured
==============================================================================

See :issue:`107032`

Description
===========

Symfony Messenger's :php:`\Symfony\Component\Messenger\Attribute\AsMessageHandler`
attribute provides a :php:`fromTransport` property to bind a message handler to a
single *receiving* transport: the handler is then only executed for messages that
were received from that transport by a worker
(:bash:`typo3 messenger:consume <transport>`).

So far TYPO3 collected the attribute but dropped the :php:`fromTransport` value
while building the handler map, so the property had no effect and a handler
declaring it was executed for the message regardless of the transport the
message was received from. This made a proper asynchronous send/receive setup -
where a message is consumed from a dedicated transport and only the matching
handler runs - impossible to express.

The value is now passed through to the handler descriptor and evaluated against
the envelope's :php:`\Symfony\Component\Messenger\Stamp\ReceivedStamp`, matching
the upstream Symfony behaviour:

..  code-block:: php

    use Symfony\Component\Messenger\Attribute\AsMessageHandler;

    // Only runs for MyMessage instances received from the "async" transport.
    #[AsMessageHandler(fromTransport: 'async')]
    final readonly class MyAsyncHandler
    {
        public function __invoke(MyMessage $message): void
        {
            // ...
        }
    }

Resolution rules:

*   A handler **without** :php:`fromTransport` keeps running for every matching
    message, regardless of the receiving transport (unchanged behaviour).
*   A handler **with** :php:`fromTransport` only runs when the message carries a
    :php:`ReceivedStamp` for one of the bound transports. A message dispatched and
    handled synchronously (no :php:`ReceivedStamp`, i.e. not received from a
    worker) is not affected by the restriction and still reaches the handler.

Binding a handler to multiple transports
========================================

A handler may be bound to more than one receiving transport. As the Symfony
:php:`AsMessageHandler` attribute only accepts a single transport name per
attribute, several transports are expressed by repeating the attribute (it is
:php:`\Attribute::IS_REPEATABLE`):

..  code-block:: php

    use Symfony\Component\Messenger\Attribute\AsMessageHandler;

    // Runs for MyMessage received from the "async" or the "priority" transport.
    #[AsMessageHandler(fromTransport: 'async')]
    #[AsMessageHandler(fromTransport: 'priority')]
    final readonly class MyAsyncHandler
    {
        public function __invoke(MyMessage $message): void
        {
            // ...
        }
    }

When a handler is registered via a service tag instead of the attribute, the
:php:`fromTransport` tag option additionally accepts a list of transport names
(:php:`string`, :php:`list<string>` or :php:`null`):

..  code-block:: yaml

    services:
      MyVendor\MyExtension\Messenger\MyAsyncHandler:
        tags:
          - name: 'messenger.message_handler'
            fromTransport: [ 'async', 'priority' ]

Duplicate and empty transport names are ignored, and the handler runs at most
once per received message even when several bindings match.

Impact
======

Installations that already annotated a handler with :php:`fromTransport` and
(knowingly or not) relied on the value being ignored will see that handler no
longer run for messages received from a different transport. Remove the
:php:`fromTransport` property from such a handler to restore the previous
"handle everything" behaviour.

..  note::

    This controls the **consumer** side (which handler runs for a received
    message). The **sender** side - which transport a message is routed to when
    dispatched - is a separate concern: a wildcard route (a namespace wildcard
    or the global :php:`'*'`) now acts as a fallback and no longer fans out in
    addition to a matching specific route (see :issue:`101699` and
    :issue:`110223`). Combining fallback routing on the sender side with a
    :php:`fromTransport`-bound handler on the consumer side is what enables a
    fully separated asynchronous send/receive setup.

Providing this change on older TYPO3 versions
=============================================

The fix spans two core classes:

*   :php:`\TYPO3\CMS\Core\Messenger\HandlersLocatorFactory` stores the transport
    binding and builds one handler descriptor per receiving transport, and
*   :php:`\TYPO3\CMS\Core\DependencyInjection\MessageHandlerPass` reads the
    :php:`fromTransport` tag option and carries it into that factory.

The factory is a regular service and can be swapped through
:file:`Configuration/Services.yaml`. The pass, however, is a Symfony
*compiler pass* registered in core's :file:`Configuration/Services.php` - it is
not a service and cannot be replaced through :file:`Services.yaml`. As the
:php:`fromTransport` value is already dropped inside that pass on an older core,
replacing only the factory is *not* sufficient: the value never reaches it.

Because a second, corrected compiler pass registered from an extension would run
*in addition* to the core one (re-adding the handler unbound and defeating the
restriction), a local extension can only provide the corrected factory, not the
corrected pass. A complete backport therefore requires a Composer patch that
adjusts both classes in :file:`typo3/cms-core`.

Backport the change via a Composer patch
----------------------------------------

Apply the change to :file:`typo3/cms-core` using a Composer patch tool such as
`cweagans/composer-patches <https://github.com/cweagans/composer-patches>`__:

..  code-block:: json

    {
        "require": {
            "cweagans/composer-patches": "^1.7"
        },
        "extra": {
            "patches": {
                "typo3/cms-core": {
                    "Honour AsMessageHandler fromTransport": "patches/messenger-from-transport.patch"
                }
            }
        }
    }

The patch file *should* contain the diff of both
:file:`typo3/sysext/core/Classes/Messenger/HandlersLocatorFactory.php` and
:file:`typo3/sysext/core/Classes/DependencyInjection/MessageHandlerPass.php`,
and is applied automatically on :bash:`composer install`.

Optionally replace the factory in a local extension
---------------------------------------------------

If the corrected pass is provided by a Composer patch, the corrected factory may
instead live in a local extension. Copy the current
:php:`HandlersLocatorFactory` into the extension, for example as
:php:`\MyVendor\MyExtension\Messenger\HandlersLocatorFactory`, and re-point the
core service to it in the extension's :file:`Configuration/Services.yaml`:

..  code-block:: yaml

    services:
      MyVendor\MyExtension\Messenger\HandlersLocatorFactory: ~

      # replace the core factory the messenger handlers locator is built from
      TYPO3\CMS\Core\Messenger\HandlersLocatorFactory:
        alias: MyVendor\MyExtension\Messenger\HandlersLocatorFactory

Because :php:`MessageHandlerPass` resolves the factory definition by the core
class name (and the alias resolves to the extension definition), the compiler
pass adds its :php:`addHandler()` calls to the extension implementation.

..  note::

    Due to the monorepo to composer package splitting a patch requires adoption
    of the paths and stripping out changes in :file:`Tests/` folders.

    See `Applying Core patches <https://docs.typo3.org/permalink/t3coreapi:applying-core-patches>`_
    for further details on how to create and apply Composer patches for TYPO3.

..  index:: CLI, PHP-API, ext:core
