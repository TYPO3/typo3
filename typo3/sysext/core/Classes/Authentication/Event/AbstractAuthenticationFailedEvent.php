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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Class to be extended by events, fired after authentication has failed
 */
abstract class AbstractAuthenticationFailedEvent
{
    public function __construct(
        private readonly ServerRequestInterface $request
    ) {
    }

    /**
     * Returns the user, who failed to authenticate successfully
     */
    abstract public function getUser(): AbstractUserAuthentication;

    public function isFrontendAttempt(): bool
    {
        return !$this->isBackendAttempt();
    }

    public function isBackendAttempt(): bool
    {
        return $this->getUser() instanceof BackendUserAuthentication;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
