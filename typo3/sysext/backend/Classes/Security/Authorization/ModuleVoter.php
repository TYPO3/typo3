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

namespace TYPO3\CMS\Backend\Security\Authorization;

use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Core\Attribute\AsSecurityVoter;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\Authorization\LegacyBackendUserRegistry;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\SecurityRole;
use TYPO3\CMS\Core\Security\Authorization\Voter\PrincipalVoter;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Decides on accessing a backend module with one of the access values of
 * TYPO3 Core: isGranted(Scope::ACCESS, $moduleRegistry->getModule('web_layout')).
 * A principal of a frontend user is denied every module. For backend users,
 * no access value grants every backend user, "admin" and "systemMaintainer"
 * require the corresponding role, "user" a grant of the module or one of its
 * aliases in the group data of the backend user object the principal was
 * built from, as BackendUserAuthentication::check() does. Without such an
 * object, "user" is denied.
 *
 * An access value of TYPO3 Core whose module access gate an extension
 * replaced is decided by that gate through the ModuleAccessGateVoter.
 *
 * The access value of a module is no closed set: an extension may use a value
 * of its own and decide it with a voter, or with a module access gate, which is
 * asked by the ModuleAccessGateVoter. A module whose access value no voter
 * decides is not accessible. The workspace restriction of a module is
 * decided by the ModuleWorkspaceVoter, as it depends on the current workspace
 * instead of a grant.
 *
 * @extends PrincipalVoter<string, ModuleInterface>
 */
#[AsSecurityVoter]
final class ModuleVoter extends PrincipalVoter
{
    /**
     * The access values of TYPO3 Core, decided by this voter
     */
    public const array ACCESS_VALUES = ['', 'user', 'admin', 'systemMaintainer'];

    public function __construct(
        private readonly LegacyBackendUserRegistry $legacyUserRegistry,
        private readonly ReplacedModuleAccessGates $replacedGates,
    ) {}

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === Scope::ACCESS;
    }

    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, ModuleInterface::class, true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof ModuleInterface
            && $attribute === Scope::ACCESS
            && in_array($subject->getAccess(), self::ACCESS_VALUES, true)
            && !$this->replacedGates->isReplaced($subject->getAccess());
    }

    protected function voteOnPrincipal(string $attribute, mixed $subject, Principal $principal, ?Vote $vote = null): bool
    {
        if (!$principal->isBackendUser()) {
            $vote?->addReason('Backend modules are accessible for backend users only.');
            return false;
        }
        return match ($subject->getAccess()) {
            '' => true,
            'user' => $this->isGrantedForModule($principal, $subject, $vote),
            'admin' => $this->requireRole($principal, SecurityRole::ADMIN, $vote),
            'systemMaintainer' => $this->requireRole($principal, SecurityRole::MAINTAINER, $vote),
            default => false,
        };
    }

    private function requireRole(Principal $principal, SecurityRole $role, ?Vote $vote): bool
    {
        if ($this->hasRole($principal, $role)) {
            return true;
        }
        $vote?->addReason(sprintf('The module requires the role "%s".', $role->value));
        return false;
    }

    private function isGrantedForModule(Principal $principal, ModuleInterface $module, ?Vote $vote): bool
    {
        if ($this->hasRole($principal, SecurityRole::ADMIN)) {
            return true;
        }
        $modules = (string)($this->legacyUserRegistry->getGroupData($principal)['modules'] ?? '');
        foreach ([$module->getIdentifier(), ...$module->getAliases()] as $identifier) {
            if (GeneralUtility::inList($modules, (string)$identifier)) {
                return true;
            }
        }
        $vote?->addReason(sprintf('The module "%s" is not granted to the user or one of its groups.', $module->getIdentifier()));
        return false;
    }
}
