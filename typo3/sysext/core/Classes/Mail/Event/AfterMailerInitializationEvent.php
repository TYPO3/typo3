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
 * This event is fired once a new Mailer is instantiated with specific transport settings.
 * So it is possible to add custom mailing settings.
 */
final class AfterMailerInitializationEvent
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function getMailer(): MailerInterface
    {
        return $this->mailer;
    }
}
