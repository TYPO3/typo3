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

namespace TYPO3\CMS\Core\Security\PermissionSet;

/**
 * Represents the identity and group membership of a principal for authorization.
 *
 * Contains the core identity information needed for permission evaluation,
 * including the role level, unique identifier, group memberships, and
 * impersonation context. This is used as part of the Principal object
 * to provide context for permission checks.
 *
 * Reading the role, the id and the group ids of the context of a Principal
 * is public API. Everything else, e.g. creating a context or the
 * impersonating context, is not part of TYPO3 Core API yet.
 */
final readonly class PrincipalContext
{
    /**
     * @param PrincipalRole $role The role level of the principal
     * @param int $id The unique identifier of the principal (user ID, system ID)
     * @param array<int> $groupIds List of group IDs the principal belongs to
     * @param self|null $impersonatedBy Optional parent context when impersonation is active, internal
     * @internal not part of TYPO3 Core API yet, contexts are created along with their principal
     */
    public function __construct(
        public PrincipalRole $role,
        public int $id,
        public array $groupIds = [],
        public ?self $impersonatedBy = null,
    ) {}
}
