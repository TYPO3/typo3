<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Mail\Event;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

/**
 * This event is fired before the Mailer has sent a message and
 * allows listeners to manipulate the RawMessage and the Envelope.
 *
 * Note: Usually TYPO3\CMS\Core\Mail\Mailer is given to the event. This implementation
 *       allows to retrieve the TransportInterface using the getTransport() method.
 */
final class BeforeMailerSentMessageEvent
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private RawMessage $message,
        private ?Envelope $envelope = null,
    ) {
    }

    public function getMessage(): RawMessage
    {
        return $this->message;
    }

    public function setMessage(RawMessage $message): void
    {
        $this->message = $message;
    }

    public function getEnvelope(): ?Envelope
    {
        return $this->envelope;
    }

    public function setEnvelope(?Envelope $envelope = null): void
    {
        $this->envelope = $envelope;
    }

    public function getMailer(): MailerInterface
    {
        return $this->mailer;
    }
}
