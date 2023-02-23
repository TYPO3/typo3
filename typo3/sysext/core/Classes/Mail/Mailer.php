<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Mail;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use TYPO3\CMS\Core\Exception as CoreException;
use TYPO3\CMS\Core\Mail\Event\AfterMailerInitializationEvent;
use TYPO3\CMS\Core\Mail\Event\AfterMailerSentMessageEvent;
use TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MailUtility;

/**
 * Adapter for Symfony/Mailer to be used by TYPO3 extensions.
 *
 * This will use the setting in TYPO3_CONF_VARS to choose the correct transport
 * for it to work out-of-the-box.
 */
class Mailer implements MailerInterface
{
    protected array $mailSettings = [];

    protected ?SentMessage $sentMessage;

    /**
     * This will be added as X-Mailer to all outgoing mails
     */
    protected string $mailerHeader = 'TYPO3';

    /**
     * When constructing, also initializes the Symfony Transport like configured
     *
     * @param TransportInterface|null $transport optionally pass a transport to the constructor.
     * @throws CoreException
     */
    public function __construct(
        protected ?TransportInterface $transport = null,
        protected readonly ?EventDispatcherInterface $eventDispatcher = null,
    ) {
        if (empty($this->mailSettings)) {
            $this->injectMailSettings();
        }

        try {
            $this->initializeTransport();
        } catch (\Exception $e) {
            throw new CoreException($e->getMessage(), 1291068569);
        }

        $this->eventDispatcher?->dispatch(new AfterMailerInitializationEvent($this));
    }

    public function send(RawMessage $message, Envelope $envelope = null): void
    {
        if ($message instanceof Email) {
            // Ensure to always have a From: header set
            if (empty($message->getFrom())) {
                $address = MailUtility::getSystemFromAddress();
                if ($address) {
                    $name = MailUtility::getSystemFromName();
                    if ($name) {
                        $from = new Address($address, $name);
                    } else {
                        $from = new Address($address);
                    }
                    $message->from($from);
                }
            }
            if (empty($message->getReplyTo())) {
                $replyTo = MailUtility::getSystemReplyTo();
                if (!empty($replyTo)) {
                    $address = key($replyTo);
                    if ($address === 0) {
                        $replyTo = new Address($replyTo[$address]);
                    } else {
                        $replyTo = new Address((string)$address, reset($replyTo));
                    }
                    $message->replyTo($replyTo);
                }
            }
            // Only set X-Mailer header once, if message is re-used
            if (!$message->getHeaders()->has('X-Mailer')) {
                $message->getHeaders()->addTextHeader('X-Mailer', $this->mailerHeader);
            }
        }

        // After static enrichment took place, allow listeners to further manipulate message and envelope
        $event = new BeforeMailerSentMessageEvent($this, $message, $envelope);
        $this->eventDispatcher?->dispatch($event);

        // Send message using the defined transport, with message and envelope from the event
        $this->sentMessage = $this->transport->send($event->getMessage(), $event->getEnvelope());

        // Finally, allow further processing by listeners after the message has been sent
        $this->eventDispatcher?->dispatch(new AfterMailerSentMessageEvent($this));
    }

    public function getSentMessage(): ?SentMessage
    {
        return $this->sentMessage;
    }

    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }

    /**
     * Prepares a transport using the TYPO3_CONF_VARS configuration
     *
     * Used options:
     * $TYPO3_CONF_VARS['MAIL']['transport'] = 'smtp' | 'sendmail' | 'null' | 'mbox'
     *
     * $TYPO3_CONF_VARS['MAIL']['transport_smtp_server'] = 'smtp.example.org:25';
     * $TYPO3_CONF_VARS['MAIL']['transport_smtp_encrypt'] = FALSE; # requires openssl in PHP
     * $TYPO3_CONF_VARS['MAIL']['transport_smtp_username'] = 'username';
     * $TYPO3_CONF_VARS['MAIL']['transport_smtp_password'] = 'password';
     *
     * $TYPO3_CONF_VARS['MAIL']['transport_sendmail_command'] = '/usr/sbin/sendmail -bs'
     *
     * @throws CoreException
     * @throws \RuntimeException
     */
    private function initializeTransport()
    {
        $this->transport ??= $this->getTransportFactory()->get($this->mailSettings);
    }

    /**
     * This method is only used in unit tests
     *
     * @internal
     */
    public function injectMailSettings(array $mailSettings = null)
    {
        $this->mailSettings = $mailSettings ?? (array)$GLOBALS['TYPO3_CONF_VARS']['MAIL'];
    }

    /**
     * Returns the real transport (not a spool).
     */
    public function getRealTransport(): TransportInterface
    {
        $mailSettings = !empty($this->mailSettings) ? $this->mailSettings : (array)$GLOBALS['TYPO3_CONF_VARS']['MAIL'];
        unset($mailSettings['transport_spool_type']);
        return $this->getTransportFactory()->get($mailSettings);
    }

    protected function getTransportFactory(): TransportFactory
    {
        return GeneralUtility::makeInstance(TransportFactory::class);
    }
}
