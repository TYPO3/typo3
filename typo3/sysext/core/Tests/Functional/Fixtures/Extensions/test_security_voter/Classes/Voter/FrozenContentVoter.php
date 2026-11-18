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
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\SecurityRole;
use TYPO3\CMS\Core\Security\Authorization\Voter\PrincipalVoter;

/**
 * Restricts a decision of TYPO3 Core, as the FrozenNewsVoter of the
 * changelog does: only administrators may change content elements.
 *
 * @extends PrincipalVoter<string, TcaSchema>
 */
#[AsSecurityVoter]
final class FrozenContentVoter extends PrincipalVoter
{
    public const string TABLE = 'tt_content';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof TcaSchema
            && $subject->getName() === self::TABLE
            && $attribute === Scope::WRITE;
    }

    protected function voteOnPrincipal(string $attribute, mixed $subject, Principal $principal, ?Vote $vote = null): bool
    {
        return $this->hasRole($principal, SecurityRole::ADMIN);
    }
}
