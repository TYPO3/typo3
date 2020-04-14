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

namespace TYPO3\CMS\FrontendLogin\Event;

use Symfony\Component\Mime\Email;

/**
 * Event that contains the email to be sent to the user when they request a new password.
 * More
 *
 * Additional validation can happen here.
 */
final class SendRecoveryEmailEvent
{
    /**
     * @var Email
     */
    private $email;

    /**
     * @var array
     */
    private $user;

    public function __construct(Email $email, array $user)
    {
        $this->email = $email;
        $this->user = $user;
    }

    public function getUserInformation(): array
    {
        return $this->user;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }
}
