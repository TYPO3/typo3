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

namespace TYPO3\CMS\Core\Tests\Functional\Security\Authorization\Page\Fixtures;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

final class PagePermissionHooks
{
    public function modifyClause(array $params, BackendUserAuthentication $backendUser): string
    {
        return $params['currentClause'] . ' AND pages.uid <> 2';
    }

    public function modifyPermissions(array $params, BackendUserAuthentication $backendUser): int
    {
        return $params['outputPermissions'] | Permission::PAGE_NEW;
    }
}
