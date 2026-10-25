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

namespace TYPO3\CMS\Core\Security\Authorization;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;
use TYPO3\CMS\Core\Authentication\Principal;

/**
 * The security token of a request, holding the authenticated Principal.
 * It is never stored in a session: authentication establishes the
 * Principal anew for every request.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication modernization groundwork
 */
final class PrincipalToken extends AbstractToken
{
    public function __construct(Principal $principal)
    {
        $user = new PrincipalUser($principal);
        parent::__construct($user->getRoles());
        parent::setUser($user);
    }

    public function getPrincipal(): Principal
    {
        /** @var PrincipalUser $user */
        $user = $this->getUser();
        return $user->principal;
    }

    public function setUser(UserInterface $user): never
    {
        throw new \LogicException('The principal of a token cannot be replaced, create a new token instead.', 1792768549);
    }

    public function __serialize(): array
    {
        throw new \LogicException('A principal token must not be serialized.', 1792768550);
    }
}
