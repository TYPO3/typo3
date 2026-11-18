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
use TYPO3\CMS\Core\Security\Authorization\Voter\PrincipalVoter;

/**
 * Asks the check of the backend user object for the custom option it
 * decides on itself, which would recurse without end.
 *
 * @extends PrincipalVoter<string, ResourceIdentifier>
 */
#[AsSecurityVoter]
final class RecursingCustomOptionVoter extends PrincipalVoter
{
    public const string CUSTOM_OPTION = 'test:recurse';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Scope::ACCESS
            && $subject instanceof ResourceIdentifier
            && $subject->type === ResourceIdentifier::CUSTOM_OPTION
            && $subject->identifier === self::CUSTOM_OPTION;
    }

    protected function voteOnPrincipal(string $attribute, mixed $subject, Principal $principal, ?Vote $vote = null): bool
    {
        return $GLOBALS['BE_USER']->check('custom_options', self::CUSTOM_OPTION);
    }
}
