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

namespace TYPO3\CMS\Core\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\Authorization\PrincipalUser;
use TYPO3\CMS\Core\Security\Authorization\SecurityRole;

/**
 * Base class of voters deciding for a Principal: the one of the request,
 * or the one given to isGrantedForUser(). A request without principal,
 * e.g. of an anonymous visitor, is denied once the voter supports the
 * attribute and subject.
 *
 * Implement supports() to state which attribute and subject the voter
 * decides, and voteOnPrincipal() to decide. Overriding
 * supportsAttribute() and supportsType() lets the access decision manager
 * skip the voter without calling supports().
 *
 * @template TAttribute of string
 * @template TSubject of mixed
 * @extends Voter<TAttribute, TSubject>
 */
abstract class PrincipalVoter extends Voter
{
    /**
     * @param TAttribute $attribute
     * @param TSubject $subject
     * @param ?Vote $vote Collects the reasons of the decision, see Vote::addReason()
     */
    abstract protected function voteOnPrincipal(string $attribute, mixed $subject, Principal $principal, ?Vote $vote = null): bool;

    final protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof PrincipalUser) {
            $vote?->addReason('No principal is authenticated.');
            return false;
        }
        return $this->voteOnPrincipal($attribute, $subject, $user->principal, $vote);
    }

    final protected function hasRole(Principal $principal, SecurityRole $role): bool
    {
        return new PrincipalUser($principal)->hasRole($role);
    }
}
