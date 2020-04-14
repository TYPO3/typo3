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

use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Database\Query\Restriction\PagePermissionRestriction;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

class PagePermissionRestrictionTest extends AbstractRestrictionTestCase
{

    /**
     * Builds a shell for the user aspect object which returns the checked values in the Restriction.
     *
     * @param bool $isLoggedIn
     * @param bool $isAdmin
     * @param int $userId
     * @param array $groupIds
     * @return UserAspect
     */
    protected function getPreparedUserAspect(bool $isLoggedIn, bool $isAdmin, int $userId, array $groupIds): UserAspect
    {
        return new class($isLoggedIn, $isAdmin, $userId, $groupIds) extends UserAspect {
            private $isAdmin;
            private $isLoggedIn;
            private $userId;
            private $groupIds;
            public function __construct(bool $isLoggedIn, bool $isAdmin, int $userId, array $groupIds)
            {
                $this->isLoggedIn = $isLoggedIn;
                $this->isAdmin = $isAdmin;
                $this->userId = $userId;
                $this->groupIds = $groupIds;
            }
            public function isAdmin(): bool
            {
                return $this->isAdmin;
            }
            public function isLoggedIn(): bool
            {
                return $this->isLoggedIn;
            }
            public function get($name)
            {
                if ($name === 'id') {
                    return $this->userId;
                }
                return parent::get($name);
            }
            public function getGroupIds(): array
            {
                return $this->groupIds;
            }
        };
    }

    /**
     * @test
     */
    public function buildRestrictionsOnlyWorksOnPagesTable()
    {
        $aspect = $this->getPreparedUserAspect(true, false, 2, [13]);
        $subject = new PagePermissionRestriction($aspect, Permission::PAGE_SHOW);
        $expression = $subject->buildExpression(['pages' => 'pages'], $this->expressionBuilder);
        self::assertNotEmpty((string)$expression);
        $expression = $subject->buildExpression(['anotherTable' => 'anotherTable'], $this->expressionBuilder);
        self::assertEmpty((string)$expression);
    }

    /**
     * @test
     */
    public function buildRestrictionsReturnsAZeroReturnSetWhenNotLoggedIn()
    {
        $aspect = $this->getPreparedUserAspect(false, false, 2, [13]);
        $subject = new PagePermissionRestriction($aspect, Permission::PAGE_SHOW);
        $expression = $subject->buildExpression(['pages' => 'pages'], $this->expressionBuilder);
        self::assertSame('1 = 0', (string)$expression);
    }

    /**
     * @test
     */
    public function buildRestrictionsIsSkippedForAdmins()
    {
        $aspect = $this->getPreparedUserAspect(true, true, 2, [13]);
        $subject = new PagePermissionRestriction($aspect, Permission::PAGE_SHOW);
        $expression = $subject->buildExpression(['pages' => 'pages'], $this->expressionBuilder);
        self::assertEmpty((string)$expression);
    }

    /**
     * @test
     */
    public function buildRestrictionsContainsNonAdminUserIdAsOwnerAndGroupIdsAsOwnerGroup()
    {
        $aspect = $this->getPreparedUserAspect(true, false, 2, [13, 14, 15, 16]);
        $subject = new PagePermissionRestriction($aspect, Permission::PAGE_SHOW);
        $expression = $subject->buildExpression(['pages' => 'pages'], $this->expressionBuilder);
        self::assertEquals('("pages"."perms_everybody" & 1 = 1) OR (("pages"."perms_userid" = 2) AND ("pages"."perms_user" & 1 = 1)) OR (("pages"."perms_groupid" IN (13, 14, 15, 16)) AND ("pages"."perms_group" & 1 = 1))', (string)$expression);
    }

    /**
     * @test
     */
    public function buildRestrictionsChecksForDeletionPermission()
    {
        $aspect = $this->getPreparedUserAspect(true, false, 42, [13, 14, 15, 16]);
        $subject = new PagePermissionRestriction($aspect, Permission::PAGE_DELETE);
        $expression = $subject->buildExpression(['pages' => 'pages'], $this->expressionBuilder);
        self::assertEquals('("pages"."perms_everybody" & 4 = 4) OR (("pages"."perms_userid" = 42) AND ("pages"."perms_user" & 4 = 4)) OR (("pages"."perms_groupid" IN (13, 14, 15, 16)) AND ("pages"."perms_group" & 4 = 4))', (string)$expression);
    }
}
