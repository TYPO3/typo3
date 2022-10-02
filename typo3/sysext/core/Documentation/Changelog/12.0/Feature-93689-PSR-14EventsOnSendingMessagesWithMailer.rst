.. include:: /Includes.rst.txt

.. _feature-93689-1654629861:

===============================================================
Feature: #93689 - PSR-14 events on sending messages with Mailer
===============================================================

See :issue:`93689`

Description
===========

TYPO3's :php:`MailerInterface` implementation :php:`Mailer` is used for sending
messages, e.g. in the EXT:form email finishers. To allow further handling and
manipulation of the message sending process, two new PSR-14 events have been
introduced.

The :php:`BeforeMailerSentMessageEvent` is dispatched before the message
is sent by the mailer and can be used to manipulate the :php:`RawMessage`
and the :php:`Envelope`. Usually an :php:`Email` or `FluidEmail` instance
is given as :php:`RawMessage`. Additionally, the :php:`MailerInstance` is
given, which depending on the implementation - usually
:php:`TYPO3\CMS\Core\Mail\Mailer` - contains the :php:`Transport` object,
which can be retrieved using the :php:`getTransport()` method.

The :php:`AfterMailerSentMessageEvent` is dispatched as soon as the
message has been sent via the corresponding :php:`TransportInterface`.
The event receives the current :php:`MailerInstance`, which, depending
on the implementation - usually :php:`TYPO3\CMS\Core\Mail\Mailer` -
contains the :php:`SentMessage` object that can be retrieved using
the :php:`getSentMessage()` method.

Registration of the event listeners in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\EventListener\MailerSentMessageEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/modify-message'
          method: 'modifyMessage'
        - name: event.listener
          identifier: 'my-package/process-sent-message'
          method: 'processSentMessage'

The corresponding event listener class:

..  code-block:: php

    use Psr\Log\LoggerInterface;
    use Symfony\Component\Mime\Address;
    use Symfony\Component\Mime\Email;
    use TYPO3\CMS\Core\Mail\Event\AfterMailerSentMessageEvent;
    use TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent;
    use TYPO3\CMS\Core\Mail\Mailer;

    final class MailerSentMessageEventListener
    {
        public function __construct(
            private readonly LoggerInterface $logger
        ) {
        }

        public function modifyMessage(BeforeMailerSentMessageEvent $event): void
        {
            $message = $event->getMessage();

            // If $message is an Email implementation, add an additional recipient
            if ($message instanceof Email) {
                $message->addCc(new Address('kasperYYYY@typo3.org'));
            }
        }

        public function processSentMessage(AfterMailerSentMessageEvent $event): void
        {
            $mailer = $event->getMailer();
            if ($mailer instanceof Mailer) {
                $sentMessage = $mailer->getSentMessage();
                if ($sentMessage !== null) {
                    $this->logger->debug($sentMessage->getDebug());
                }
            }
        }
    }

Impact
======

With the new PSR-14 events, it's now possible to manipulate messages before
they are sent by the mailer. Additionally, after the mailer has sent messages,
further processing can be performed.

.. index:: PHP-API, ext:core
