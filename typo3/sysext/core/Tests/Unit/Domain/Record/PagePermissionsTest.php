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

namespace TYPO3\CMS\Core\Tests\Unit\Domain\Record;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Domain\Record\PagePermissions;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PagePermissionsTest extends UnitTestCase
{
    private const array PAGE_ROW = [
        'uid' => 3,
        'pid' => 1,
        'doktype' => 1,
        'perms_userid' => 7,
        'perms_groupid' => '4',
        'perms_user' => Permission::ALL,
        'perms_group' => Permission::PAGE_SHOW | Permission::CONTENT_EDIT,
        'perms_everybody' => Permission::NOTHING,
    ];

    #[Test]
    public function fromRecordReadsPermissionFields(): void
    {
        $subject = PagePermissions::fromRecord(self::PAGE_ROW);
        self::assertSame(7, $subject->getOwnerUserId());
        self::assertSame(4, $subject->getOwnerGroupId());
        self::assertSame(Permission::ALL, $subject->getOwnerPermissions()->__toInt());
        self::assertTrue($subject->getGroupPermissions()->editContentPermissionIsGranted());
        self::assertFalse($subject->getGroupPermissions()->editPagePermissionIsGranted());
        self::assertTrue($subject->getEverybodyPermissions()->nothingIsGranted());
    }

    #[Test]
    public function fromRecordReturnsNullIfAPermissionFieldIsMissing(): void
    {
        $row = self::PAGE_ROW;
        unset($row['perms_everybody']);
        self::assertNull(PagePermissions::fromRecord($row));
    }

    #[Test]
    public function returnedPermissionsDoNotChangeTheSettings(): void
    {
        $subject = PagePermissions::fromRecord(self::PAGE_ROW);
        $subject->getEverybodyPermissions()->set(Permission::PAGE_SHOW);
        self::assertTrue($subject->getEverybodyPermissions()->nothingIsGranted());
    }

    #[Test]
    public function pageReturnsPermissions(): void
    {
        self::assertEquals(PagePermissions::fromRecord(self::PAGE_ROW), new Page(self::PAGE_ROW)->getPermissions());
    }

    #[Test]
    public function pageWithoutPermissionFieldsReturnsNull(): void
    {
        self::assertNull(new Page(['uid' => 3, 'pid' => 1, 'doktype' => 1])->getPermissions());
    }
}
