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

namespace TYPO3\CMS\Core\Security\Authorization;

use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;

/**
 * The roles a PrincipalUser holds, as the security component expects them:
 * strings starting with "ROLE_", decided by its RoleVoter, e.g.
 * isGranted(SecurityRole::ADMIN->value). The roles of a group are no case,
 * as they carry the uid of the group, see PrincipalUser.
 *
 * There is no role for anonymous requests: they have no principal, and
 * the attributes of the AuthenticatedVoter of the security component
 * decide on them, e.g. isGranted(AuthenticatedVoter::IS_AUTHENTICATED).
 */
enum SecurityRole: string
{
    /**
     * The base role of the security component: every authenticated
     * principal of every realm, backend and frontend users alike. Use
     * BACKEND_USER or FRONTEND_USER to tell them apart.
     */
    case USER = 'ROLE_USER';

    /**
     * Administrators, system maintainers and the system
     */
    case ADMIN = 'ROLE_ADMIN';

    /**
     * System maintainers and the system
     */
    case MAINTAINER = 'ROLE_MAINTAINER';

    /**
     * The system itself, e.g. CLI and internal processes
     */
    case SYSTEM = 'ROLE_SYSTEM';

    /**
     * Every principal of the backend realm
     */
    case BACKEND_USER = 'ROLE_BACKEND_USER';

    /**
     * Every principal of a frontend realm. This is the "show at any login"
     * group (-2) of the fe_group field; "hide at login" (-1) is a request
     * without principal, see AuthenticatedVoter::IS_AUTHENTICATED.
     */
    case FRONTEND_USER = 'ROLE_FRONTEND_USER';

    /**
     * The roles granted by a principal role. They are cumulative: an admin
     * also holds the role of a user, a maintainer the one of an admin, and
     * the system every role.
     *
     * @return list<self>
     */
    public static function fromPrincipalRole(PrincipalRole $role): array
    {
        return match ($role) {
            PrincipalRole::USER => [self::USER],
            PrincipalRole::ADMIN => [self::USER, self::ADMIN],
            PrincipalRole::MAINTAINER => [self::USER, self::ADMIN, self::MAINTAINER],
            PrincipalRole::SYSTEM => [self::USER, self::ADMIN, self::MAINTAINER, self::SYSTEM],
        };
    }
}
