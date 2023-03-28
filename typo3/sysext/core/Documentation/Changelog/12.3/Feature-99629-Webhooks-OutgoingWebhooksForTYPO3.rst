.. include:: /Includes.rst.txt

.. _feature-99629-1674550092:

========================================================
Feature: #99629 - Webhooks - Outgoing webhooks for TYPO3
========================================================

See :issue:`99629`

Description
===========

A webhook is an automated message sent from one application to another via HTTP.

This feature adds the possibility to configure webhooks in TYPO3.

A new backend module :guilabel:`System > Webhooks` provides the possibility to
configure webhooks. The module is available in the TYPO3 backend for users with
administrative rights.

A webhook is defined as an authorized POST or GET request to a defined URL.
For example, a webhook can be used to send a notification to a Slack channel
when a new page is created in TYPO3.

Any webhook record is defined by a universally unique identifier (UUID), a speaking name, an optional
description, a trigger, the target URL and a signing-secret.
Both the unique identifier and the signing-secret are generated in the backend
when a new webhook is created.

Triggers provided by the TYPO3 Core
-----------------------------------

The TYPO3 Core currently provides the following triggers for webhooks:

* Page Modification: Triggers when a page is created, updated or deleted
* File Added: Triggers when a file is added
* File Updated: Triggers when a file is updated
* File Removed: Triggers when a file is removed
* Login Error Occurred: Triggers when a login error occurred
* Redirect Was Hit: Triggers when a redirect has been hit

These triggers are meant as a first set of triggers that can be used to send webhooks,
further triggers will be added in the future. In most projects however, it is likely
that custom triggers are required.

Custom triggers
---------------

Trigger by PSR-14 events
~~~~~~~~~~~~~~~~~~~~~~~~

Custom triggers can be added by creating a `Message` for an specific PSR-14 event and
tagging that message as a webhook message.

The following example shows how to create a simple webhook message for the
:php:`\TYPO3\CMS\Core\Resource\Event\AfterFolderAddedEvent`:

..  code-block:: php

    namespace TYPO3\CMS\Webhooks\Message;

    use TYPO3\CMS\Core\Attribute\WebhookMessage;
    use TYPO3\CMS\Core\Messaging\WebhookMessageInterface;
    use TYPO3\CMS\Core\Resource\Event\AfterFolderAddedEvent;

    #[WebhookMessage(
        identifier: 'typo3/folder-added',
        description: 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.webhook_type.typo3-folder-added'
    )]
    final class FolderAddedMessage implements WebhookMessageInterface
    {
        public function __construct(
            private readonly int $storageUid,
            private readonly string $identifier,
            private readonly string $publicUrl
        ) {
        }

        public static function createFromEvent(AfterFolderAddedEvent $event): self
        {
            $file = $event->getFile();
            return new self($file->getStorage()->getUid(), $file->getIdentifier(), $file->getPublicUrl());
        }

        public function jsonSerialize(): array
        {
            return [
                'storage' => $this->storageUid,
                'identifier' => $this->identifier,
                'url' => $this->publicUrl,
            ];
        }
    }

#.  Create a final class implementing `\TYPO3\CMS\Core\Messaging\WebhookMessageInterface`.
#.  Add the :php:`\TYPO3\CMS\Core\Attribute\WebhookMessage` attribute to the class.
    The attribute requires the following information:

    *   `identifier`: The identifier of the webhook message.
    *   `description`: The description of the webhook message. This description
        is used to describe the trigger in the TYPO3 backend.

#.  Add a static method `createFromEvent()` that creates a new instance of the
    message from the event you want to use as a trigger.
#.  Add a method `jsonSerialize()` that returns an array with the data that
    should be sent with the webhook.

Trigger by hooks or custom code
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In case a trigger is not provided by the TYPO3 Core or a PSR-14 event is not available,
it is possible to create a custom trigger - for example by using a TYPO3 hook.

The message itself should look similar to the example above, but does not need the
:php:`createFromEvent()` method.

Instead, the custom code (hook implementation) will create the message
and dispatch it.

Example hook implementation for a DataHandler hook (see :php:`\TYPO3\CMS\Webhooks\Listener\PageModificationListener`):

..  code-block:: php

    public function __construct(
        protected readonly \Symfony\Component\Messenger\MessageBusInterface $bus
    ) {
    }

    public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, DataHandler $dataHandler)
    {
        if ($table !== 'pages') {
            return;
        }
        // ...
        $message = new PageModificationMessage(
                'new',
                $id,
                $fieldArray,
                $site->getIdentifier(),
                (string)$site->getRouter()->generateUri($id),
                $dataHandler->BE_USER,
        );
        // ...
        $this->bus->dispatch($message);
    }

Use :file:`Services.yaml` instead of the PHP attribute
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Instead of the PHP attribute the :file:`Services.yaml` can be used to define the
webhook message. The following example shows how to define the webhook message
from the example above in the :file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    TYPO3\CMS\Webhooks\Message\FolderAddedMessage:
        tags:
          - name: 'core.webhook_message'
            identifier: 'typo3/folder-added'
            description: 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.webhook_type.typo3-folder-added'


HTTP headers of every webhook
-----------------------------

With every webhook request, the following HTTP headers are sent:

* Content-Type: application/json
* Webhook-Signature-Algo: sha256
* Webhook-Signature: <hash>

The hash is calculated with the secret of the webhook and the JSON encoded data
of the request. The hash is created with the PHP function :php:`hash_hmac`.
See the following section about the hash calculation.

Hash calculation
----------------

The hash is calculated with the following PHP code:

..  code-block:: php

    $hash = hash_hmac('sha256', sprintf(
        '%s:%s',
        $identifier, // The identifier of the webhook (uuid)
        $body // The JSON encoded body of the request
    ), $secret); // The secret of the webhook

The hash is sent as HTTP header `Webhook-Signature` and should be used to
validate that the request was sent from the TYPO3 instance and has not been
manipulated.
To verify this on the receiving end, build the hash with the same algorithm and
secret and compare it with the hash that was sent with the request.

The hash is not meant to be used as a security mechanism, but as a way to verify
that the request was sent from the TYPO3 instance.

Technical background and advanced usage
---------------------------------------

The webhook system is based on the Symfony Messenger component. The messages
are simple PHP objects that implement an interface that denotes
them as webhook messages.

That message is then dispatched to the Symfony Messenger bus. The TYPO3 Core
provides a :php:`\TYPO3\CMS\Webhooks\MessageHandler\WebhookMessageHandler`
that is responsible for sending the webhook
requests to the third-party system, if configured to do so. The handler looks up
the webhook configuration and sends the request to the configured URL.

Messages are sent to the bus in any case. The handler is then responsible for checking
whether or not an external request (webhook) should be sent.

If advanced request handling is necessary or a custom implementation should be used,
a custom handler can be created that handles :php:`WebhookMessageInterface`
messages.

..  seealso::
    :ref:`More information on messages and their handlers <t3coreapi:message-bus>`

Impact
======

The TYPO3 Core now provides a convenient GUI to create and send webhooks to
third-party systems.
In combination with the system extension :doc:`reactions <ext_reactions:Index>`
TYPO3 can now be used as a
low-code/no-code integration platform between multiple systems.

.. index:: Backend, Frontend, PHP-API, ext:webhooks
