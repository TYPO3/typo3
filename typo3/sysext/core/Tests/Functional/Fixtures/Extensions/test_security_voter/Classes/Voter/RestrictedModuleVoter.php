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
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Core\Attribute\AsSecurityVoter;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\SecurityRole;
use TYPO3\CMS\Core\Security\Authorization\Voter\PrincipalVoter;

/**
 * Restricts the module "test_restricted_module" to administrators, in
 * addition to what its access value requires.
 *
 * @extends PrincipalVoter<string, ModuleInterface>
 */
#[AsSecurityVoter]
final class RestrictedModuleVoter extends PrincipalVoter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Scope::ACCESS
            && $subject instanceof ModuleInterface
            && $subject->getIdentifier() === 'test_restricted_module';
    }

    protected function voteOnPrincipal(string $attribute, mixed $subject, Principal $principal, ?Vote $vote = null): bool
    {
        return $this->hasRole($principal, SecurityRole::ADMIN);
    }
}
