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

namespace TYPO3Tests\TestSecurityVoter\Grant;

use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\Authorization\Policy\AdminOnlyTableGrantInterface;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;

/**
 * Lets the editor of the fixture read and change backend users, as an
 * extension delegating the management of editors to non-admins does.
 */
final readonly class BackendUserManagerGrant implements AdminOnlyTableGrantInterface
{
    public const int USER_ID = 2;

    public function isGranted(Principal $principal, string $scope, string $table): bool
    {
        return $table === 'be_users'
            && in_array($scope, [Scope::READ, Scope::WRITE], true)
            && $principal->context->id === self::USER_ID;
    }
}
