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

namespace TYPO3Tests\TestSecurityVoter\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use TYPO3\CMS\Core\Attribute\AsSecurityVoter;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\Authorization\Policy\ResourceIdentifier;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\SecurityRole;
use TYPO3\CMS\Core\Security\Authorization\Voter\PrincipalVoter;

/**
 * Decides on a resource type of its own, which the policy decision point of
 * TYPO3 Core does not know: the discount "staff" is granted to administrators.
 *
 * @extends PrincipalVoter<string, ResourceIdentifier>
 */
#[AsSecurityVoter]
final class DiscountVoter extends PrincipalVoter
{
    public const string RESOURCE_TYPE = 'tx_test_discount';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Scope::ACCESS
            && $subject instanceof ResourceIdentifier
            && $subject->type === self::RESOURCE_TYPE;
    }

    protected function voteOnPrincipal(string $attribute, mixed $subject, Principal $principal, ?Vote $vote = null): bool
    {
        return $subject->identifier === 'staff' && $this->hasRole($principal, SecurityRole::ADMIN);
    }
}
