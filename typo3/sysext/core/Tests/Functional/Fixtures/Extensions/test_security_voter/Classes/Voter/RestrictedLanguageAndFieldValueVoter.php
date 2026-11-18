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
 * Restricts decisions of TYPO3 Core on a language and on a value of an
 * authMode field: only administrators may change them.
 *
 * @extends PrincipalVoter<string, ResourceIdentifier>
 */
#[AsSecurityVoter]
final class RestrictedLanguageAndFieldValueVoter extends PrincipalVoter
{
    public const int LANGUAGE = 1;
    public const string TABLE = 'tt_content';
    public const string FIELD = 'CType';
    public const string VALUE = 'text';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Scope::WRITE
            && $subject instanceof ResourceIdentifier
            && (
                ($subject->type === ResourceIdentifier::LANGUAGE && $subject->identifier === (string)self::LANGUAGE)
                || ($subject->type === ResourceIdentifier::FIELD_VALUE && $subject->identifier === self::TABLE . ':' . self::FIELD . ':' . self::VALUE)
            );
    }

    protected function voteOnPrincipal(string $attribute, mixed $subject, Principal $principal, ?Vote $vote = null): bool
    {
        return $this->hasRole($principal, SecurityRole::ADMIN);
    }
}
