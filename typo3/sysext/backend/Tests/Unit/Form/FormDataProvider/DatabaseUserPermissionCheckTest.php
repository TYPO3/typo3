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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Form\Event\ModifyEditFormUserAccessEvent;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedContentEditException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedEditInternalsException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedListenerException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedPageEditException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedPageNewException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedRootNodeException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedTableModifyException;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DatabaseUserPermissionCheckTest extends UnitTestCase
{
    protected BackendUserAuthentication&MockObject $beUserMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beUserMock = $this->createMock(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $this->beUserMock;
        $GLOBALS['BE_USER']->user['uid'] = 42;
    }

    #[Test]
    public function addDataSetsUserPermissionsOnPageForAdminUser(): void
    {
        $this->beUserMock->method('isAdmin')->willReturn(true);
        $result = (new DatabaseUserPermissionCheck(new NoopEventDispatcher()))->addData([]);
        self::assertSame(Permission::ALL, $result['userPermissionOnPage']);
    }

    #[Test]
    public function addDataThrowsExceptionIfUserHasNoTablesModifyPermissionForGivenTable(): void
    {
        $input = [
            'tableName' => 'tt_content',
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $this->beUserMock->method('check')->with('tables_modify', $input['tableName'])->willReturn(false);

        $this->expectException(AccessDeniedTableModifyException::class);
        $this->expectExceptionCode(1437683248);

        (new DatabaseUserPermissionCheck(new NoopEventDispatcher()))->addData($input);
    }

    #[Test]
    public function addDataThrowsExceptionIfUserHasNoContentEditPermissionsOnPage(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 123,
            'databaseRow' => [],
            'parentPageRow' => [
                'uid' => 42,
                'pid' => 321,
            ],
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $this->beUserMock->method('check')->with('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserMock->method('calcPerms')->with(['uid' => 42, 'pid' => 321])->willReturn(Permission::NOTHING);

        $this->expectException(AccessDeniedContentEditException::class);
        $this->expectExceptionCode(1437679657);

        (new DatabaseUserPermissionCheck(new NoopEventDispatcher()))->addData($input);
    }

    #[Test]
    public function addDataAddsUserPermissionsOnPageForContentIfUserHasCorrespondingPermissions(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 123,
            'databaseRow' => [],
            'parentPageRow' => [
                'pid' => 321,
            ],
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $this->beUserMock->method('check')->with('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserMock->method('calcPerms')->with(['pid' => 321])->willReturn(Permission::CONTENT_EDIT);
        $this->beUserMock->method('recordEditAccessInternals')->with($input['tableName'], self::anything())->willReturn(true);
        $result = (new DatabaseUserPermissionCheck(new NoopEventDispatcher()))->addData($input);
        self::assertSame(Permission::CONTENT_EDIT, $result['userPermissionOnPage']);
    }

    #[Test]
    public function addDataThrowsExceptionIfCommandIsEditTableIsPagesAndUserHasNoPagePermissions(): void
    {
        $input = [
            'tableName' => 'pages',
            'command' => 'edit',
            'vanillaUid' => 123,
            'databaseRow' => [
                'uid' => 123,
                'pid' => 321,
            ],
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $this->beUserMock->method('check')->with('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserMock->method('calcPerms')->with($input['databaseRow'])->willReturn(Permission::NOTHING);

        $this->expectException(AccessDeniedPageEditException::class);
        $this->expectExceptionCode(1437679336);

        (new DatabaseUserPermissionCheck(new NoopEventDispatcher()))->addData($input);
    }

    #[Test]
    public function addDataThrowsExceptionIfCommandIsEditTableIsPagesAndUserHasNoDoktypePermissions(): void
    {
        $input = [
            'tableName' => 'pages',
            'command' => 'edit',
            'vanillaUid' => 123,
            'databaseRow' => [
                'uid' => 123,
                'pid' => 321,
                'doktype' => 1,
            ],
            'processedTca' => [
                'ctrl' => [
                    'type' => 'doktype',
                ],
            ],
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $series = [
            [['type' => 'tables_modify', 'value' => $input['tableName']], true],
            [['type' => 'pagetypes_select', 'value' => $input['databaseRow']['doktype']], false],
        ];
        $this->beUserMock->method('check')->willReturnCallback(function (string $type, string|int $value) use (&$series): bool {
            [$expectedArgs, $return] = array_shift($series);
            self::assertSame($expectedArgs['type'], $type);
            self::assertSame($expectedArgs['value'], $value);
            return $return;
        });
        $this->beUserMock->method('recordEditAccessInternals')->with($input['tableName'], self::anything())->willReturn(true);
        $this->beUserMock->method('calcPerms')->with($input['databaseRow'])->willReturn(Permission::ALL);

        $this->expectException(AccessDeniedPageEditException::class);
        $this->expectExceptionCode(1437679336);

        (new DatabaseUserPermissionCheck(new NoopEventDispatcher()))->addData($input);
    }

    #[Test]
    public function addDataAddsUserPermissionsOnPageIfTableIsPagesAndUserHasPagePermissions(): void
    {
        $input = [
            'tableName' => 'pages',
            'command' => 'edit',
            'vanillaUid' => 123,
            'databaseRow' => [
                'uid' => 123,
                'pid' => 321,
                'doktype' => 1,
            ],
            'processedTca' => [
                'ctrl' => [
                    'type' => 'doktype',
                ],
            ],
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $series = [
            ['type' => 'tables_modify', 'value' => $input['tableName']],
            ['type' => 'pagetypes_select', 'value' => $input['databaseRow']['doktype']],
        ];
        $this->beUserMock->method('check')->willReturnCallback(function (string $type, string|int $value) use (&$series): bool {
            $expectedArgs = array_shift($series);
            self::assertSame($expectedArgs['type'], $type);
            self::assertSame($expectedArgs['value'], $value);
            return true;
        });
        $this->beUserMock->method('calcPerms')->with($input['databaseRow'])->willReturn(Permission::PAGE_EDIT);
        $this->beUserMock->method('recordEditAccessInternals')->with($input['tableName'], self::anything())->willReturn(true);

        $result = (new DatabaseUserPermissionCheck(new NoopEventDispatcher()))->addData($input);

        self::assertSame(Permission::PAGE_EDIT, $result['userPermissionOnPage']);
    }

    #[Test]
    public function addDataSetsPermissionsToAllIfRootLevelRestrictionForTableIsIgnoredForContentEditRecord(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 123,
            'databaseRow' => [
                'uid' => 123,
                'pid' => 0,
            ],
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $this->beUserMock->method('check')->with('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserMock->method('recordEditAccessInternals')->with($input['tableName'], self::anything())->willReturn(true);
        $GLOBALS['TCA'][$input['tableName']]['ctrl']['security']['ignoreRootLevelRestriction'] = true;

        $result = (new DatabaseUserPermissionCheck(new NoopEventDispatcher()))->addData($input);

        self::assertSame(Permission::ALL, $result['userPermissionOnPage']);
    }

    #[Test]
    public function addDataThrowsExceptionIfRootNodeShouldBeEditedWithoutPermissions(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 123,
            'databaseRow' => [
                'uid' => 123,
                'pid' => 0,
            ],
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $this->beUserMock->method('check')->with('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserMock->method('recordEditAccessInternals')->with($input['tableName'], self::anything())->willReturn(true);

        $this->expectException(AccessDeniedRootNodeException::class);
        $this->expectExceptionCode(1437679856);

        (new DatabaseUserPermissionCheck(new NoopEventDispatcher()))->addData($input);
    }

    #[Test]
    public function addDataThrowsExceptionIfRecordEditAccessInternalsReturnsFalse(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 123,
            'databaseRow' => [],
            'parentPageRow' => [
                'uid' => 123,
                'pid' => 321,
            ],
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $this->beUserMock->method('check')->with('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserMock->method('calcPerms')->with($input['parentPageRow'])->willReturn(Permission::ALL);
        $this->beUserMock->method('recordEditAccessInternals')->with($input['tableName'], self::anything())->willReturn(false);

        $this->expectException(AccessDeniedEditInternalsException::class);
        $this->expectExceptionCode(1437687404);

        (new DatabaseUserPermissionCheck(new NoopEventDispatcher()))->addData($input);
    }

    #[Test]
    public function addDataThrowsExceptionForNewContentRecordWithoutPermissions(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'new',
            'vanillaUid' => 123,
            'databaseRow' => [],
            'parentPageRow' => [
                'uid' => 123,
                'pid' => 321,
            ],
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $this->beUserMock->method('check')->with('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserMock->method('calcPerms')->with($input['parentPageRow'])->willReturn(Permission::NOTHING);

        $this->expectException(AccessDeniedContentEditException::class);
        $this->expectExceptionCode(1437745759);

        (new DatabaseUserPermissionCheck(new NoopEventDispatcher()))->addData($input);
    }

    #[Test]
    public function addDataThrowsExceptionForNewPageWithoutPermissions(): void
    {
        $input = [
            'tableName' => 'pages',
            'command' => 'new',
            'vanillaUid' => 123,
            'databaseRow' => [
                'uid' => 'NEW123',
            ],
            'parentPageRow' => [
                'uid' => 123,
                'pid' => 321,
            ],
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $this->beUserMock->method('check')->with('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserMock->method('calcPerms')->with($input['parentPageRow'])->willReturn(Permission::NOTHING);

        $this->expectException(AccessDeniedPageNewException::class);
        $this->expectExceptionCode(1437745640);

        (new DatabaseUserPermissionCheck(new NoopEventDispatcher()))->addData($input);
    }

    #[Test]
    public function addDataThrowsExceptionIfHookDeniesAccess(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 123,
            'databaseRow' => [
                'uid' => 5,
            ],
            'parentPageRow' => [
                'uid' => 123,
                'pid' => 321,
            ],
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $this->beUserMock->method('check')->with('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserMock->method('calcPerms')->with($input['parentPageRow'])->willReturn(Permission::ALL);
        $this->beUserMock->method('recordEditAccessInternals')->with($input['tableName'], self::anything())->willReturn(true);

        $this->expectException(AccessDeniedListenerException::class);
        $this->expectExceptionCode(1662727149);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())->method('dispatch')->willReturnCallback(static function (ModifyEditFormUserAccessEvent $event) {
            $event->denyUserAccess();
            return $event;
        });
        (new DatabaseUserPermissionCheck($eventDispatcher))->addData($input);
    }

    #[Test]
    public function addDataSetsUserPermissionsOnPageForNewPageIfPageNewIsDeniedAndHookAllowsAccess(): void
    {
        $input = [
            'tableName' => 'pages',
            'command' => 'new',
            'vanillaUid' => 123,
            'databaseRow' => [
                'uid' => 'NEW5',
            ],
            'parentPageRow' => [
                'uid' => 123,
                'pid' => 321,
            ],
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $this->beUserMock->method('check')->with('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserMock->method('calcPerms')->with($input['parentPageRow'])->willReturn(Permission::CONTENT_EDIT);
        $this->beUserMock->method('recordEditAccessInternals')->with($input['tableName'], self::anything())->willReturn(true);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())->method('dispatch')->willReturnCallback(static function (ModifyEditFormUserAccessEvent $event) {
            $event->allowUserAccess();
            return $event;
        });
        $result = (new DatabaseUserPermissionCheck($eventDispatcher))->addData($input);
        self::assertSame(Permission::CONTENT_EDIT, $result['userPermissionOnPage']);
    }

    #[Test]
    public function addDataSetsUserPermissionsOnPageForNewPage(): void
    {
        $input = [
            'tableName' => 'pages',
            'command' => 'new',
            'vanillaUid' => 123,
            'databaseRow' => [],
            'parentPageRow' => [
                'uid' => 123,
                'pid' => 321,
            ],
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $this->beUserMock->method('check')->with('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserMock->method('calcPerms')->with($input['parentPageRow'])->willReturn(Permission::PAGE_NEW);
        $this->beUserMock->method('recordEditAccessInternals')->with($input['tableName'], self::anything())->willReturn(true);
        $result = (new DatabaseUserPermissionCheck(new NoopEventDispatcher()))->addData($input);
        self::assertSame(Permission::PAGE_NEW, $result['userPermissionOnPage']);
    }

    #[Test]
    public function addDataSetsUserPermissionsOnPageForNewContentRecord(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'new',
            'vanillaUid' => 123,
            'databaseRow' => [],
            'parentPageRow' => [
                'uid' => 123,
                'pid' => 321,
            ],
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $this->beUserMock->method('check')->with('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserMock->method('calcPerms')->with($input['parentPageRow'])->willReturn(Permission::CONTENT_EDIT);
        $this->beUserMock->method('recordEditAccessInternals')->with($input['tableName'], self::anything())->willReturn(true);
        $result = (new DatabaseUserPermissionCheck(new NoopEventDispatcher()))->addData($input);
        self::assertSame(Permission::CONTENT_EDIT, $result['userPermissionOnPage']);
    }

    #[Test]
    public function addDataSetsPermissionsToAllIfRootLevelRestrictionForTableIsIgnoredForNewContentRecord(): void
    {
        $input = [
            'tableName' => 'pages',
            'command' => 'new',
            'vanillaUid' => 123,
            'databaseRow' => [],
            'parentPageRow' => null,
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $this->beUserMock->method('check')->with('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserMock->method('recordEditAccessInternals')->with($input['tableName'], self::anything())->willReturn(true);
        $GLOBALS['TCA'][$input['tableName']]['ctrl']['security']['ignoreRootLevelRestriction'] = true;
        $result = (new DatabaseUserPermissionCheck(new NoopEventDispatcher()))->addData($input);
        self::assertSame(Permission::ALL, $result['userPermissionOnPage']);
    }

    #[Test]
    public function addDataThrowsExceptionForNewRecordsOnRootLevelWithoutPermissions(): void
    {
        $input = [
            'tableName' => 'pages',
            'command' => 'new',
            'vanillaUid' => 123,
            'databaseRow' => [],
            'parentPageRow' => null,
        ];

        $this->beUserMock->method('isAdmin')->willReturn(false);
        $this->beUserMock->method('check')->with('tables_modify', $input['tableName'])->willReturn(true);

        $this->expectException(AccessDeniedRootNodeException::class);
        $this->expectExceptionCode(1437745221);
        (new DatabaseUserPermissionCheck(new NoopEventDispatcher()))->addData($input);
    }
}
