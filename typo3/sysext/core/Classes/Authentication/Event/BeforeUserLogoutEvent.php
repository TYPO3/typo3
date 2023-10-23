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

namespace TYPO3\CMS\Core\Authentication\Event;

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Session\UserSession;

/**
 * Event fired before a user is going to be actively logged out.
 * An option to interrupt the regular logout flow from TYPO3 Core (so you can do this yourself)
 * is also available.
 */
final class BeforeUserLogoutEvent
{
    private bool $shouldLogout = true;

    public function __construct(
        private readonly AbstractUserAuthentication $user,
        private readonly ?UserSession $userSession
    ) {}

    public function getUser(): AbstractUserAuthentication
    {
        return $this->user;
    }

    public function disableRegularLogoutProcess(): void
    {
        $this->shouldLogout = false;
    }

    public function enableRegularLogoutProcess(): void
    {
        $this->shouldLogout = true;
    }

    public function shouldLogout(): bool
    {
        return $this->shouldLogout;
    }

    public function getUserSession(): ?UserSession
    {
        return $this->userSession;
    }
}
