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

use Symfony\Component\Mailer\MailerInterface;

/**
 * This event is fired once a Mailer has sent a message and allows listeners to execute
 * further code afterwards, depending on the result, e.g. the SentMessage.
 *
 * Note: Usually TYPO3\CMS\Core\Mail\Mailer is given to the event. This implementation
 *       allows to retrieve the SentMessage using the getSentMessage() method. Depending
 *       on the Transport, used to send the message, this might also be NULL.
 */
final class AfterMailerSentMessageEvent
{
    public function __construct(private readonly MailerInterface $mailer) {}

    public function getMailer(): MailerInterface
    {
        return $this->mailer;
    }
}
