:navigation-title: Advanced usage
..  include:: /Includes.rst.txt

..  _advanced-usage:

=======================================
Technical background and advanced usage
=======================================

The webhook system is based on the Symfony Messenger component. The messages
are simple PHP objects that implement an interface that denotes
them as Webhook messages.

That message is then dispatched to the Symfony Messenger bus. The TYPO3 Core
provides a :php-short:`\TYPO3\CMS\Webhooks\MessageHandler\WebhookMessageHandler`
that is responsible for sending the webhook
requests to the third-party system if configured to do so. The handler looks up
the webhook configuration and sends the request to the configured URL.

Messages are sent to the bus in any case. The handler is then responsible for checking
whether or not an external request (webhook) should be sent.

If advanced request handling is necessary or a custom implementation should be used,
a custom handler can be created that handles
:php-short:`\TYPO3\CMS\Core\Messaging\WebhookMessageInterface`
messages. See the :ref:`Message bus <t3coreapi:message-bus>` for more information
on messages and their handlers.
