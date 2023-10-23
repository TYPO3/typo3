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
use TYPO3\CMS\Core\Security\RequestToken;

/**
 * Event fired before request-token is processed.
 */
final class BeforeRequestTokenProcessedEvent
{
    public function __construct(
        private AbstractUserAuthentication $user,
        private ServerRequestInterface $request,
        private RequestToken|false|null $requestToken
    ) {}

    public function getUser(): AbstractUserAuthentication
    {
        return $this->user;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getRequestToken(): RequestToken|false|null
    {
        return $this->requestToken;
    }

    public function setRequestToken(RequestToken|false|null $requestToken): void
    {
        $this->requestToken = $requestToken;
    }
}
