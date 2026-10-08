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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves a request to an authentication outcome by asking the
 * authenticators registered for the realm in order of their priority.
 *
 * Authenticators registered for "frontend" serve the frontend realm of
 * every site. The first authenticator that returns anything other than
 * NotApplicable determines the outcome. If none does, NotApplicable is
 * returned and the caller decides how to treat the anonymous request. A
 * principal established for another realm than the requested one is
 * refused.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication pipeline groundwork
 */
final readonly class AuthenticatorManager
{
    /**
     * @param ContainerInterface $authenticators the authenticators by service id
     * @param array<string, list<string>> $authenticatorIdsByRealm service ids per registered realm, ordered by priority
     */
    public function __construct(
        private ContainerInterface $authenticators,
        private array $authenticatorIdsByRealm,
    ) {}

    public function authenticate(ServerRequestInterface $request, Realm $realm): AuthenticationResult
    {
        foreach ($this->authenticatorIdsByRealm[$realm->getRegistrationIdentifier()] ?? [] as $id) {
            /** @var AuthenticatorInterface $authenticator */
            $authenticator = $this->authenticators->get($id);
            $result = $authenticator->authenticate($request, $realm);
            if ($result instanceof Authenticated && $result->principal->realmIdentifier !== $realm->identifier) {
                throw new \LogicException(
                    sprintf(
                        'Authenticator "%s" established a principal of realm "%s" for realm "%s".',
                        $authenticator::class,
                        $result->principal->realmIdentifier,
                        $realm->identifier
                    ),
                    1791491203
                );
            }
            if (!$result instanceof NotApplicable) {
                return $result;
            }
        }
        return new NotApplicable();
    }
}
