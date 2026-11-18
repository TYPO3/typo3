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

namespace TYPO3\CMS\Core\Security\Authorization\Policy;

use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\Authorization\LegacyBackendUserRegistry;
use TYPO3\CMS\Core\Security\Authorization\PrincipalUser;
use TYPO3\CMS\Core\Security\Authorization\SecurityRole;

/**
 * Decides on the permission lists of be_users and be_groups records, as
 * BackendUserAuthentication::check(), checkLanguageAccess() and
 * checkAuthMode() do.
 *
 * Principals with the role "system", "maintainer" or "admin" are granted
 * every scope before any lookup. Frontend principals are denied, as their
 * groups are no be_groups. The group data is the one of a backend user
 * object, including groups added through the AfterGroupsResolvedEvent,
 * so that there is only one source of group data as long as the backend
 * user object exists: the group data registered for the principal in the
 * LegacyBackendUserRegistry by the LegacyUserPrincipalFactory. Any other
 * principal is denied.
 *
 * | Resource type    | Scope       | Granted by                                     |
 * |------------------|-------------|------------------------------------------------|
 * | table            | read        | tables_select or tables_modify                 |
 * | table            | write       | tables_modify                                  |
 * | admin_only_table | read, write | an AdminOnlyTableGrantInterface, never a group |
 * | field            | write       | non_exclude_fields                             |
 * | field_value      | write       | explicit_allowdeny                             |
 * | language         | write       | allowed_languages, all if empty, always for -1 |
 * | page_type        | write       | pagetypes_select                               |
 * | module           | access      | userMods or groupMods                          |
 * | widget           | access      | available_widgets or availableWidgets          |
 * | mfa_provider     | access      | mfa_providers                                  |
 * | custom_option    | access      | custom_options                                 |
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication modernization groundwork
 */
final class LegacyGroupDataPolicyDecisionPoint implements PolicyDecisionPointInterface
{
    private const array LISTS = [
        ResourceIdentifier::TABLE => [
            Scope::READ => 'tables_select',
            Scope::WRITE => 'tables_modify',
        ],
        ResourceIdentifier::FIELD => [Scope::WRITE => 'non_exclude_fields'],
        ResourceIdentifier::FIELD_VALUE => [Scope::WRITE => 'explicit_allowdeny'],
        ResourceIdentifier::LANGUAGE => [Scope::WRITE => 'allowed_languages'],
        ResourceIdentifier::PAGE_TYPE => [Scope::WRITE => 'pagetypes_select'],
        ResourceIdentifier::MODULE => [Scope::ACCESS => 'modules'],
        ResourceIdentifier::WIDGET => [Scope::ACCESS => 'available_widgets'],
        ResourceIdentifier::MFA_PROVIDER => [Scope::ACCESS => 'mfa_providers'],
        ResourceIdentifier::CUSTOM_OPTION => [Scope::ACCESS => 'custom_options'],
    ];

    private const array ADMIN_ONLY_TABLE_SCOPES = [Scope::READ, Scope::WRITE];

    /**
     * @var \WeakMap<Principal, LegacyGroupData>
     */
    private \WeakMap $parsedGroupData;

    /**
     * @param iterable<AdminOnlyTableGrantInterface> $adminOnlyTableGrants
     */
    public function __construct(
        private readonly LegacyBackendUserRegistry $legacyUserRegistry,
        private readonly iterable $adminOnlyTableGrants = [],
    ) {
        $this->parsedGroupData = new \WeakMap();
    }

    public function supports(string $scope, ResourceIdentifier $resource): bool
    {
        if ($resource->type === ResourceIdentifier::ADMIN_ONLY_TABLE) {
            return in_array($scope, self::ADMIN_ONLY_TABLE_SCOPES, true);
        }
        return isset(self::LISTS[$resource->type][$scope]);
    }

    public function decide(Principal $principal, string $scope, ResourceIdentifier $resource): bool
    {
        if (!$principal->isBackendUser()) {
            return false;
        }
        if (!$this->supports($scope, $resource)) {
            return false;
        }
        if ($this->isPrivileged($principal)) {
            return true;
        }
        if ($resource->type === ResourceIdentifier::ADMIN_ONLY_TABLE) {
            return $this->isAdminOnlyTableGranted($principal, $scope, $resource->identifier);
        }
        $list = self::LISTS[$resource->type][$scope];
        $groupData = $this->getParsedGroupData($principal);
        if ($groupData === null) {
            return false;
        }
        if ($resource->type === ResourceIdentifier::LANGUAGE) {
            return $groupData->isEmpty($list)
                || $resource->identifier === '-1'
                || $groupData->contains($list, $resource->identifier);
        }
        return $groupData->contains($list, $resource->identifier);
    }

    /**
     * The group data registered for the principal, parsed once per principal.
     * A principal is registered once, a changed user object gets a new one.
     */
    private function getParsedGroupData(Principal $principal): ?LegacyGroupData
    {
        if (!isset($this->parsedGroupData[$principal])) {
            $groupData = $this->legacyUserRegistry->getGroupData($principal);
            if ($groupData === null) {
                return null;
            }
            $this->parsedGroupData[$principal] = LegacyGroupData::fromGroupData($groupData);
        }
        return $this->parsedGroupData[$principal];
    }

    private function isAdminOnlyTableGranted(Principal $principal, string $scope, string $table): bool
    {
        foreach ($this->adminOnlyTableGrants as $grant) {
            if ($grant->isGranted($principal, $scope, $table)) {
                return true;
            }
        }
        return false;
    }

    private function isPrivileged(Principal $principal): bool
    {
        return new PrincipalUser($principal)->hasRole(SecurityRole::ADMIN);
    }
}
