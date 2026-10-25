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
use TYPO3\CMS\Core\Security\Authorization\Voter\PrincipalVoter;

/**
 * Decides on modules with the access value "test_project_lead", which is
 * not known to TYPO3 Core: members of the backend group 3 are granted.
 *
 * @extends PrincipalVoter<string, ModuleInterface>
 */
#[AsSecurityVoter]
final class ProjectLeadModuleVoter extends PrincipalVoter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Scope::ACCESS
            && $subject instanceof ModuleInterface
            && $subject->getAccess() === 'test_project_lead';
    }

    protected function voteOnPrincipal(string $attribute, mixed $subject, Principal $principal, ?Vote $vote = null): bool
    {
        return in_array(3, $principal->context->groupIds, true);
    }
}
