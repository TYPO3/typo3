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
 * Principal roles for determining privilege levels.
 *
 * Defines the role-based access control levels:
 * - SYSTEM: System-level operations (CLI, install tool internal)
 * - MAINTAINER: Install tool users, system maintenance tasks
 * - ADMIN: Backend administrators (bypasses permission checks)
 * - USER: Regular backend users (respects permission grants)
 *
 * @internal
 */
enum PrincipalRole: string
{
    case SYSTEM = 'system';
    case MAINTAINER = 'maintainer';
    case ADMIN = 'admin';
    case USER = 'user';
}
