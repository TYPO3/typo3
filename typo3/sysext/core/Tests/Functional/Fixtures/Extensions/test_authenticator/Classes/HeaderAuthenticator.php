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
use TYPO3\CMS\Core\Authentication\Pipeline\Authenticated;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticationResult;
use TYPO3\CMS\Core\Authentication\Pipeline\AuthenticatorInterface;
use TYPO3\CMS\Core\Authentication\Pipeline\NotApplicable;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalContext;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;

/**
 * Authenticates the user named in the "X-Test-User" header, in the
 * backend and in the "api" realm.
 */
#[AsAuthenticator(realm: 'backend', priority: 10)]
#[AsAuthenticator(realm: 'api')]
final class HeaderAuthenticator implements AuthenticatorInterface
{
    public function authenticate(ServerRequestInterface $request, Realm $realm): AuthenticationResult
    {
        $username = $request->getHeaderLine('X-Test-User');
        if ($username === '') {
            return new NotApplicable();
        }
        return new Authenticated(
            new Principal(new PrincipalContext(PrincipalRole::USER, 1), $realm->identifier, $username),
            ['authenticator' => self::class]
        );
    }
}
