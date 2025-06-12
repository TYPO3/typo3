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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Query\Restriction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Database\Query\Restriction\PagePermissionRestriction;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

final class PagePermissionRestrictionTest extends AbstractRestrictionTestCase
{
    #[Test]
    public function buildRestrictionsOnlyWorksOnPagesTable(): void
    {
        $user = new BackendUserAuthentication();
        $user->user = [
            'uid' => 2,
        ];
        $aspect = new UserAspect($user, [13]);
        $subject = new PagePermissionRestriction($aspect, Permission::PAGE_SHOW);
        $expression = $subject->buildExpression(['pages' => 'pages'], $this->expressionBuilder);
        self::assertNotEmpty((string)$expression);
        $expression = $subject->buildExpression(['anotherTable' => 'anotherTable'], $this->expressionBuilder);
        self::assertEmpty((string)$expression);
    }

    #[Test]
    public function buildRestrictionsReturnsAZeroReturnSetWhenNotLoggedIn(): void
    {
        $user = new BackendUserAuthentication();
        $user->user = [
            'uid' => 0,
        ];
        $aspect = new UserAspect($user);
        $subject = new PagePermissionRestriction($aspect, Permission::PAGE_SHOW);
        $expression = $subject->buildExpression(['pages' => 'pages'], $this->expressionBuilder);
        self::assertSame('1 = 0', (string)$expression);
    }

    #[Test]
    public function buildRestrictionsIsSkippedForAdmins(): void
    {
        $user = new BackendUserAuthentication();
        $user->user = [
            'uid' => 2,
            'admin' => 1,
        ];
        $aspect = new UserAspect($user, [13]);
        $subject = new PagePermissionRestriction($aspect, Permission::PAGE_SHOW);
        $expression = $subject->buildExpression(['pages' => 'pages'], $this->expressionBuilder);
        self::assertEmpty((string)$expression);
    }

    #[Test]
    public function buildRestrictionsContainsNonAdminUserIdAsOwnerAndGroupIdsAsOwnerGroup(): void
    {
        $user = new BackendUserAuthentication();
        $user->user = [
            'uid' => 2,
            'admin' => 0,
        ];
        $aspect = new UserAspect($user, [13, 14, 15, 16]);
        $subject = new PagePermissionRestriction($aspect, Permission::PAGE_SHOW);
        $expression = $subject->buildExpression(['pages' => 'pages'], $this->expressionBuilder);
        self::assertEquals('(("pages"."perms_everybody" & 1 = 1) OR ((("pages"."perms_userid" = 2) AND ("pages"."perms_user" & 1 = 1))) OR ((("pages"."perms_groupid" IN (13, 14, 15, 16)) AND ("pages"."perms_group" & 1 = 1))))', (string)$expression);
    }

    #[Test]
    public function buildRestrictionsChecksForDeletionPermission(): void
    {
        $user = new BackendUserAuthentication();
        $user->user = [
            'uid' => 42,
            'admin' => 0,
        ];
        $aspect = new UserAspect($user, [13, 14, 15, 16]);
        $subject = new PagePermissionRestriction($aspect, Permission::PAGE_DELETE);
        $expression = $subject->buildExpression(['pages' => 'pages'], $this->expressionBuilder);
        self::assertEquals('(("pages"."perms_everybody" & 4 = 4) OR ((("pages"."perms_userid" = 42) AND ("pages"."perms_user" & 4 = 4))) OR ((("pages"."perms_groupid" IN (13, 14, 15, 16)) AND ("pages"."perms_group" & 4 = 4))))', (string)$expression);
    }
}
