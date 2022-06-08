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

namespace TYPO3\CMS\Core\Mail;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Interface for mailers for sending emails. This should be used when injecting or creating an instance of the Mailer
 * class, so it can be easily overridden.
 */
interface MailerInterface extends \Symfony\Component\Mailer\MailerInterface
{
    public function getSentMessage(): ?SentMessage;

    public function getTransport(): TransportInterface;

    public function getRealTransport(): TransportInterface;
}
