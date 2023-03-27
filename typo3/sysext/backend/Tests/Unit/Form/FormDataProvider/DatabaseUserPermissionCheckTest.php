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
use TYPO3\CMS\Core\Tests\Unit\Fixtures\EventDispatcher\MockEventDispatcher;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DatabaseUserPermissionCheckTest extends UnitTestCase
{
    protected BackendUserAuthentication&MockObject $beUserMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beUserMock = $this->createMock(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $this->beUserMock;
        $GLOBALS['BE_USER']->user['uid'] = 42;
    }

    /**
     * @test
     */
    public function addDataSetsUserPermissionsOnPageForAdminUser(): void
    {
        $this->beUserMock->method('isAdmin')->willReturn(true);

        $result = (new DatabaseUserPermissionCheck())->addData([]);

        self::assertSame(Permission::ALL, $result['userPermissionOnPage']);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfUserHasNoTablesModifyPermissionForGivenTable(): void
    {
        $input = [
            'tableName' => 'tt_content',
        ];
        $this->beUserMock->method('isAdmin')->willReturn(false);
        $this->beUserMock->method('check')->with('tables_modify', $input['tableName'])->willReturn(false);

        $this->expectException(AccessDeniedTableModifyException::class);
        $this->expectExceptionCode(1437683248);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
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

        $eventDispatcher = new MockEventDispatcher();
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
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

        $eventDispatcher = new MockEventDispatcher();
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        $result = (new DatabaseUserPermissionCheck())->addData($input);

        self::assertSame(Permission::CONTENT_EDIT, $result['userPermissionOnPage']);
    }

    /**
     * @test
     */
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

        $eventDispatcher = new MockEventDispatcher();
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
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

        $eventDispatcher = new MockEventDispatcher();
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
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

        $eventDispatcher = new MockEventDispatcher();
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        $result = (new DatabaseUserPermissionCheck())->addData($input);

        self::assertSame(Permission::PAGE_EDIT, $result['userPermissionOnPage']);
    }

    /**
     * @test
     */
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

        $eventDispatcher = new MockEventDispatcher();
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        $result = (new DatabaseUserPermissionCheck())->addData($input);

        self::assertSame(Permission::ALL, $result['userPermissionOnPage']);
    }

    /**
     * @test
     */
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

        $eventDispatcher = new MockEventDispatcher();
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
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

        $eventDispatcher = new MockEventDispatcher();
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
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

        $eventDispatcher = new MockEventDispatcher();
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
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

        $eventDispatcher = new MockEventDispatcher();
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
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

        $eventDispatcher = new MockEventDispatcher();
        $eventDispatcher->addListener(static function (ModifyEditFormUserAccessEvent $event) {
            $event->denyUserAccess();
        });
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
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

        $eventDispatcher = new MockEventDispatcher();
        $eventDispatcher->addListener(static function (ModifyEditFormUserAccessEvent $event) {
            $event->allowUserAccess();
        });
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);

        $result = (new DatabaseUserPermissionCheck())->addData($input);

        self::assertSame(Permission::CONTENT_EDIT, $result['userPermissionOnPage']);
    }

    /**
     * @test
     */
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

        GeneralUtility::addInstance(EventDispatcherInterface::class, new NoopEventDispatcher());

        $result = (new DatabaseUserPermissionCheck())->addData($input);

        self::assertSame(Permission::PAGE_NEW, $result['userPermissionOnPage']);
    }

    /**
     * @test
     */
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

        GeneralUtility::addInstance(EventDispatcherInterface::class, new NoopEventDispatcher());

        $result = (new DatabaseUserPermissionCheck())->addData($input);

        self::assertSame(Permission::CONTENT_EDIT, $result['userPermissionOnPage']);
    }

    /**
     * @test
     */
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

        GeneralUtility::addInstance(EventDispatcherInterface::class, new NoopEventDispatcher());

        $result = (new DatabaseUserPermissionCheck())->addData($input);

        self::assertSame(Permission::ALL, $result['userPermissionOnPage']);
    }

    /**
     * @test
     */
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

        GeneralUtility::addInstance(EventDispatcherInterface::class, new NoopEventDispatcher());

        (new DatabaseUserPermissionCheck())->addData($input);
    }
}
