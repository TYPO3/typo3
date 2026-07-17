..  include:: /Includes.rst.txt

..  _important-101699-1784329899:

================================================================
Important: #101699 - Messenger wildcard routes act as a fallback
================================================================

See :issue:`101699`

Description
===========

The Symfony Messenger transport routing configured via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing']` maps a message
*type* to one or more transport identifiers.

So far a wildcard route - a namespace wildcard (:php:`My\Message\*`) or the
global wildcard :php:`'*'` - was *always* applied *in addition* to any matching
specific route. A message that matched both a specific route and a wildcard
route was therefore dispatched to both transports. Because TYPO3 ships a default
catch-all route :php:`'*' => 'default'`, every routed message was silently sent
to the ``default`` transport as well, and routing a message to its specific
transport *only* required removing the wildcard route explicitly, typically in
an extension's :file:`ext_localconf.php`:

..  code-block:: php

    // previously required to avoid the additional wildcard routing
    unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing']['*']);

A wildcard route now acts as a **fallback**: it is skipped as soon as a more
specific type already matched, and only applies when no specific route matched.
This mirrors the upstream Symfony :php:`SendersLocator` resolution, so the
shipped default :php:`'*' => 'default'` route now behaves as a true fallback and
the manual :php:`unset()` of the wildcard route is no longer needed:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'] = [
        // handled by the "doctrine" transport only, the '*' route is skipped
        \My\Extension\Message\ImportMessage::class => 'doctrine',
        // fallback for every message without a more specific route
        '*' => 'default',
    ];

Single and multiple transports per route
========================================

A routing value may be given as a single transport identifier (a string) or as
an ordered list of transport identifiers (an array) to route a single message to
several transports on purpose:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'] = [
        // single transport
        \My\Extension\Message\ImportMessage::class => 'doctrine',
        // sent to "audit" and "doctrine", in that order
        \My\Extension\Message\AuditableInterface::class => ['audit', 'doctrine'],
        '*' => 'default',
    ];

The legacy mapping syntax using a plain string
(:php:`\My\Message::class => 'doctrine'`) keeps working unchanged and is treated
as the single-element array form (:php:`\My\Message::class => ['doctrine']`).

Wildcards are supported on every namespace level
================================================

Wildcard routing is not limited to the global :php:`'*'` route. A namespace
wildcard may be configured on *any* level and is likewise treated as a fallback
that is only used when no more specific route matched:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['messenger']['routing'] = [
        // every message below \My\Extension\Message\Import\
        \My\Extension\Message\Import\* => 'import',
        // every other message below \My\Extension\Message\
        \My\Extension\Message\* => 'doctrine',
        // global fallback
        '*' => 'default',
    ];

How a message is matched against the routing configuration
==========================================================

The routing configuration is a plain lookup map; its key *order* is irrelevant.
The order in which routes are considered comes from the **type chain of the
dispatched message**, derived from the message's PHP type (via
:php:`\Symfony\Component\Messenger\Handler\HandlersLocator::listTypes()`), not
from the order of the entries in the global configuration array.

For a dispatched message the type chain is evaluated from most to least
specific:

#.  the concrete message class,
#.  its parent classes (bottom-up),
#.  its implemented interfaces,
#.  the namespace wildcards for every namespace level (for example
    :php:`My\Extension\Message\Import\*`, :php:`My\Extension\Message\*`,
    :php:`My\Extension\*`, :php:`My\*`),
#.  and finally the global wildcard :php:`'*'`.

Senders are collected across every matching type in that order and deduplicated
(first occurrence wins). The first non-wildcard match therefore "locks in" the
transports and every following wildcard route is skipped. This resolution
depends solely on the class, parent and interface hierarchy of the message and
is entirely independent of the shape or ordering of the
:php:`['SYS']['messenger']['routing']` array - the configuration only provides
the mapping from a type identifier to its transports.

Impact
======

Installations that (knowingly or not) relied on a message being routed to both
its specific transport *and* the wildcard transport - most notably the default
:php:`'*' => 'default'` route - will see such a message routed to its specific
transport only. Configure the additional transport explicitly as a list value
(:php:`\My\Message::class => ['specific', 'default']`) where the previous
fan-out is still wanted.

Providing this change on older TYPO3 versions
=============================================

The change is contained in the core
:php:`\TYPO3\CMS\Core\Messenger\TransportLocator`. Projects that need the
fallback resolution on an older TYPO3 version that does not ship it yet (for
example TYPO3 v12) can obtain it in one of two ways.

Replace the core implementation via Symfony dependency injection
----------------------------------------------------------------

Copy the current :php:`TransportLocator` into an extension, for example as
:php:`\MyVendor\MyExtension\Messenger\FallbackTransportLocator`, keeping the
:php:`#[AutowireLocator('messenger.sender', indexAttribute: 'identifier')]`
constructor argument. Then re-point the messenger senders-locator to it in the
extension's :file:`Configuration/Services.yaml`:

..  code-block:: yaml

    services:
      MyVendor\MyExtension\Messenger\FallbackTransportLocator:
        arguments:
          $sendersLocator: !tagged_locator { tag: 'messenger.sender', index_by: 'identifier' }

      # replace the core implementation used for message routing
      TYPO3\CMS\Core\Messenger\TransportLocator:
        alias: MyVendor\MyExtension\Messenger\FallbackTransportLocator

      Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface:
        alias: MyVendor\MyExtension\Messenger\FallbackTransportLocator

Because the messenger senders-locator is resolved through this alias, the
extension implementation is used instead of the core one and the fallback
resolution becomes available on the older core version. If the
:php:`WebhookTypesProvider` display of the resolved transports is required as
well, copy its adjusted version accordingly.

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
                    "Messenger wildcard routing fallback": "patches/messenger-wildcard-fallback.patch"
                }
            }
        }
    }

The patch file *should* contain the diff of
:file:`typo3/sysext/core/Classes/Messenger/TransportLocator.php` (and, if used,
the :file:`WebhookTypesProvider.php` adjustment as a :composer:`typo3/cms-webhooks`
patch file) and is applied automatically on :bash:`composer install`.

..  note::

    Due to the monorepo to composer package splitting this requires adoption of
    the paths and stripping out changes in :file:`Tests/` folders, and for each
    affected TYPO3 system extension a dedicated patch file needs to be created
    and provided.

    See `Applying Core patches <https://docs.typo3.org/permalink/t3coreapi:applying-core-patches>`_
    for further details on how to create and apply Composer patches for TYPO3.

..  index:: LocalConfiguration, PHP-API, ext:core
