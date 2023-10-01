.. include:: /Includes.rst.txt

.. _feature-99632-1674121967:

===================================================================
Feature: #99632 - Introduce PHP attribute to mark a webhook message
===================================================================

See :issue:`99632`

Description
===========

A new custom PHP attribute :php:`\TYPO3\CMS\Core\Attribute\WebhookMessage` has
been added in order to register a message as a specific webhook message,
to send as remote status.

The attribute must have an identifier for the webhook type (unique),
and a description that explains the purpose of the message.

Optionally, a property method can be set for the attribute,
that contains the factory method. By default this is `createFromEvent`,
which is typically used when creating a message by an event listener, see
webhooks documentation for more details.

Example
-------

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\WebhookMessage;

    #[WebhookMessage(
        identifier: 'typo3/file-updated',
        description: 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.webhook_type.typo3-file-updated'
    )]
    final class AnyKindOfMessage
    {
        // ...
    }


Impact
======

It is now possible to tag any PHP class as webhook message by the PHP attribute
:php:`\TYPO3\CMS\Core\Attribute\WebhookMessage`.

.. index:: Backend, Frontend, PHP-API, ext:core
