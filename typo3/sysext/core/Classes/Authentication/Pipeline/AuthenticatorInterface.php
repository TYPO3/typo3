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

namespace TYPO3\CMS\Core\Authentication\Pipeline;

use Psr\Http\Message\ServerRequestInterface;

/**
 * A single, composable step in the authentication pipeline of a realm.
 *
 * Implementations are registered for the realms they serve with the
 * AsAuthenticator attribute, e.g. #[AsAuthenticator(realm: 'backend',
 * priority: 100)], and are asked by the AuthenticatorManager only for
 * these realms, those with a higher priority first. An authenticator
 * resolves the request to one of the four AuthenticationResult outcomes,
 * a request it has no credentials for to NotApplicable.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication pipeline groundwork
 */
interface AuthenticatorInterface
{
    public function authenticate(ServerRequestInterface $request, Realm $realm): AuthenticationResult;
}
