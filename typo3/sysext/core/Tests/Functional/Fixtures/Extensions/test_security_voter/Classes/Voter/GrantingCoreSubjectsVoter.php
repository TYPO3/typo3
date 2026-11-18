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
use TYPO3\CMS\Core\Security\Authorization\Policy\ResourceIdentifier;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\Subject\SchemaField;
use TYPO3\CMS\Core\Security\Authorization\Voter\PrincipalVoter;

/**
 * Grants every table, field and resource identifier of a type of TYPO3 Core,
 * which must not grant what a voter of TYPO3 Core denies or does not decide.
 *
 * @extends PrincipalVoter<string, TcaSchema|SchemaField|ResourceIdentifier>
 */
#[AsSecurityVoter]
final class GrantingCoreSubjectsVoter extends PrincipalVoter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [Scope::READ, Scope::WRITE, Scope::ACCESS], true)
            && ($subject instanceof TcaSchema
                || $subject instanceof SchemaField
                || ($subject instanceof ResourceIdentifier && !str_starts_with($subject->type, 'tx_')));
    }

    protected function voteOnPrincipal(string $attribute, mixed $subject, Principal $principal, ?Vote $vote = null): bool
    {
        return true;
    }
}
