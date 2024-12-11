.. include:: /Includes.rst.txt

.. _feature-101544-1691063522:

===========================================================================
Feature: #101544 - Introduce PHP attribute to autoconfigure event listeners
===========================================================================

See :issue:`101544`

Description
===========

A new custom PHP attribute :php:`\TYPO3\CMS\Core\Attribute\AsEventListener`
has been added in order to autoconfigure a class as a PSR-14 event listener.

The attribute supports the following properties, which are all optional,
as if you would register the listener by manually tagging it in the
:file:`Configuration/Services.yaml` or :file:`Configuration/Services.php` file:

*   `identifier` - Event listener identifier (unique) - uses the service name,
    if not provided
*   `event` - Fully-qualified class name of the PSR-14 event to listen to
*   `method` - Method to be called - if omitted, :php:`__invoke()` is called by
    the listener provider.
*   `before` - List of listener identifiers
*   `after` - List of listener identifiers

The attribute can be used on class and method level. Additionally, the new
attribute is repeatable, which allows to register the same class to listen
for different events.

Migration example
=================

Before:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\EventListener\AddMailMessageBcc:
      tags:
        - name: event.listener
          identifier: 'my-extension/add-mail-message-bcc'

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AddMailMessageBcc.php

    <?php

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent;
    use TYPO3\CMS\Core\Mail\MailMessage;

    final readonly class AddMailMessageBcc
    {
        public function __invoke(BeforeMailerSentMessageEvent $event): void
        {
            $message = $event->getMessage();
            if ($message instanceof MailMessage) {
                $message->addBcc('me@example.com');
            }
            $event->setMessage($message);
        }
    }

After:

The configuration is removed from the :file:`Services.yaml` file and the
attribute is assigned to the class instead:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AddMailMessageBcc.php

    <?php

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent;
    use TYPO3\CMS\Core\Mail\MailMessage;

    #[AsEventListener(
        identifier: 'my-extension/add-mail-message-bcc'
    )]
    final readonly class AddMailMessageBcc
    {
        public function __invoke(BeforeMailerSentMessageEvent $event): void
        {
            $message = $event->getMessage();
            if ($message instanceof MailMessage) {
                $message->addBcc('me@example.com');
            }
            $event->setMessage($message);
        }
    }


Repeatable example
==================

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/MailerEventListener.php

    <?php

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Mail\Event\AfterMailerSentMessageEvent;
    use TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent;

    #[AsEventListener(
        identifier: 'my-extension/mailer-after-sent-message',
        event: AfterMailerSentMessageEvent::class
    )]
    #[AsEventListener(
        identifier: 'my-extension/mailer-before-sent-message',
        event: BeforeMailerSentMessageEvent::class
    )]
    final readonly class MailerEventListener
    {
        public function __invoke(
            AfterMailerSentMessageEvent | BeforeMailerSentMessageEvent $event
        ): void {
            // do something
        }
    }


Method level example
====================

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/MailerEventListener.php

    <?php

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Mail\Event\AfterMailerSentMessageEvent;
    use TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent;

    final readonly class MailerEventListener
    {
        #[AsEventListener(
            identifier: 'my-extension/mailer-after-sent-message',
            event: AfterMailerSentMessageEvent::class
        )]
        #[AsEventListener(
            identifier: 'my-extension/mailer-before-sent-message',
            event: BeforeMailerSentMessageEvent::class
        )]
        public function __invoke(
            AfterMailerSentMessageEvent | BeforeMailerSentMessageEvent $event
        ): void {
            // do something
        }
    }

Impact
======

Using the PHP attribute :php:`\TYPO3\CMS\Core\Attribute\AsEventListener`, it is
now possible to tag any PHP class as an event listener. By adding the attribute
the class is automatically tagged as `event.listener` and is therefore
autoconfigured by the
:php:`\TYPO3\CMS\Core\DependencyInjection\ListenerProviderPass`.

.. index:: Backend, Frontend, PHP-API, ext:core
