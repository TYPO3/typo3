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

namespace TYPO3Tests\TestAuthenticator;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Attribute\AsAuthenticator;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticationResult;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorInterface;
use TYPO3\CMS\Core\Authentication\Pipeline\Failure;
use TYPO3\CMS\Core\Authentication\Pipeline\NotApplicable;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;

/**
 * Refuses the user "blocked" in the backend before any other
 * authenticator is asked, and serves the frontend of every site.
 */
#[AsAuthenticator(realm: 'backend')]
#[AsAuthenticator(realm: 'frontend')]
final class BlockingAuthenticator implements AuthenticatorInterface
{
    public function authenticate(ServerRequestInterface $request, Realm $realm): AuthenticationResult
    {
        if ($realm->isFrontend()) {
            return new Failure('frontend');
        }
        return $request->getHeaderLine('X-Test-User') === 'blocked' ? new Failure('blocked') : new NotApplicable();
    }
}
