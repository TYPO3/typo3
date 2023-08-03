.. include:: /Includes.rst.txt

.. _feature-101544-1691063522:

===========================================================================
Feature: #101544 - Introduce PHP attribute to autoconfigure event listeners
===========================================================================

See :issue:`101544`

Description
===========

A new custom PHP attribute :php:`\TYPO3\CMS\Core\Attribute\AsEventListener` has
been added in order to autoconfigure a class as an event listener.

The attribute must declar an `identifier` for the event listener (unique),
the optional `before` and `after` attributes allow for custom sorting
against `identifier`.

The optional `event` attribute is the FQN of the PSR-14 Event.

If no `method` attribute is given, the class is treated as Invokable,
thus `__invoke` method is called by the listener provider.

The new attributes is repeatable, which allows to register the same class
to listen for different events.

Migration example
=================

Before:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyCompany\MyExtension\EventListener\NullMailer:
      tags:
        - name: event.listener
          identifier: 'my-extension/null-mailer'

..  code-block:: php

    <?php

    namespace MyCompany\MyExtension\EventListener;

    use TYPO3\CMS\Core\Mail\Event\AfterMailerInitializationEvent;

    final class NullMailer
    {
        public function __invoke(AfterMailerInitializationEvent $event): void
        {
            $event->getMailer()->injectMailSettings(['transport' => 'null']);
        }
    }

After:

The configuration is removed from the :file:`Service.yaml` and the attribute is
assigned to the class instead:

..  code-block:: php

    <?php

    namespace MyCompany\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Mail\Event\AfterMailerInitializationEvent;

    #[AsEventListener(
        identifier: 'my-extension/null-mailer'
    )]
    final class NullMailer
    {
        public function __invoke(AfterMailerInitializationEvent $event): void
        {
            $event->getMailer()->injectMailSettings(['transport' => 'null']);
        }
    }


Repeatable example
==================

..  code-block:: php

    <?php

    namespace MyCompany\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Mail\Event\AfterMailerInitializationEvent;
    use TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent;

    #[AsEventListener(identifier: 'my-extension/null-mailer-initialization')]
    #[AsEventListener(identifier: 'my-extension/null-mailer-sent-message')]
    final class NullMailer
    {
        public function __invoke(
            AfterMailerInitializationEvent | BeforeMailerSentMessageEvent $event
        ): void {
            $event->getMailer()->injectMailSettings(['transport' => 'null']);
        }
    }

Impact
======

Using the PHP attribute :php:`\TYPO3\CMS\Core\Attribute\AsEventListener`, it is
now possible to tag any PHP class as an event listener. By adding the attribute
the class is automatically tagged as `event.listener` and is therefore auto
configured by the :php:`ListenerProviderPass`.

.. index:: Backend, Frontend, PHP-API, ext:core
