<?php

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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedContentEditException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedEditInternalsException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedHookException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedPageEditException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedPageNewException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedRootNodeException;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedTableModifyException;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DatabaseUserPermissionCheckTest extends UnitTestCase
{
    /**
     * @var BackendUserAuthentication|ObjectProphecy
     */
    protected $beUserProphecy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->beUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $this->beUserProphecy->reveal();
        $GLOBALS['BE_USER']->user['uid'] = 42;
    }

    /**
     * @test
     */
    public function addDataSetsUserPermissionsOnPageForAdminUser()
    {
        $this->beUserProphecy->isAdmin()->willReturn(true);

        $result = (new DatabaseUserPermissionCheck())->addData([]);

        self::assertSame(Permission::ALL, $result['userPermissionOnPage']);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfUserHasNoTablesModifyPermissionForGivenTable()
    {
        $input = [
            'tableName' => 'tt_content',
        ];
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(false);

        $this->expectException(AccessDeniedTableModifyException::class);
        $this->expectExceptionCode(1437683248);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfUserHasNoContentEditPermissionsOnPage()
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'edit',
            'vanillaUid' => 123,
            'parentPageRow' => [
                'uid' => 42,
                'pid' => 321,
            ],
        ];
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->calcPerms(['uid' => 42, 'pid' => 321])->willReturn(Permission::NOTHING);

        $this->expectException(AccessDeniedContentEditException::class);
        $this->expectExceptionCode(1437679657);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
    public function addDataAddsUserPermissionsOnPageForContentIfUserHasCorrespondingPermissions()
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
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->calcPerms(['pid' => 321])->willReturn(Permission::CONTENT_EDIT);
        $this->beUserProphecy->recordEditAccessInternals($input['tableName'], Argument::any())->willReturn(true);

        $result = (new DatabaseUserPermissionCheck())->addData($input);

        self::assertSame(Permission::CONTENT_EDIT, $result['userPermissionOnPage']);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfCommandIsEditTableIsPagesAndUserHasNoPagePermissions()
    {
        $input = [
            'tableName' => 'pages',
            'command' => 'edit',
            'vanillaUid' => 123,
            'databaseRow' => [
                'uid' => 123,
                'pid' => 321
            ],
        ];
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->calcPerms($input['databaseRow'])->willReturn(Permission::NOTHING);

        $this->expectException(AccessDeniedPageEditException::class);
        $this->expectExceptionCode(1437679336);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfCommandIsEditTableIsPagesAndUserHasNoDoktypePermissions()
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
                    'type' => 'doktype'
                ]
            ]
        ];
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->check('pagetypes_select', $input['databaseRow']['doktype'])->willReturn(false);
        $this->beUserProphecy->recordEditAccessInternals($input['tableName'], Argument::cetera())->willReturn(true);
        $this->beUserProphecy->calcPerms($input['databaseRow'])->willReturn(Permission::ALL);

        $this->expectException(AccessDeniedPageEditException::class);
        $this->expectExceptionCode(1437679336);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
    public function addDataAddsUserPermissionsOnPageIfTableIsPagesAndUserHasPagePermissions()
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
                    'type' => 'doktype'
                ]
            ]
        ];
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->check('pagetypes_select', $input['databaseRow']['doktype'])->willReturn(true);
        $this->beUserProphecy->calcPerms($input['databaseRow'])->willReturn(Permission::PAGE_EDIT);
        $this->beUserProphecy->recordEditAccessInternals($input['tableName'], Argument::cetera())->willReturn(true);

        $result = (new DatabaseUserPermissionCheck())->addData($input);

        self::assertSame(Permission::PAGE_EDIT, $result['userPermissionOnPage']);
    }

    /**
     * @test
     */
    public function addDataSetsPermissionsToAllIfRootLevelRestrictionForTableIsIgnoredForContentEditRecord()
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
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->recordEditAccessInternals($input['tableName'], Argument::cetera())->willReturn(true);
        $GLOBALS['TCA'][$input['tableName']]['ctrl']['security']['ignoreRootLevelRestriction'] = true;

        $result = (new DatabaseUserPermissionCheck())->addData($input);

        self::assertSame(Permission::ALL, $result['userPermissionOnPage']);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfRootNodeShouldBeEditedWithoutPermissions()
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
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->recordEditAccessInternals($input['tableName'], Argument::cetera())->willReturn(true);

        $this->expectException(AccessDeniedRootNodeException::class);
        $this->expectExceptionCode(1437679856);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfRecordEditAccessInternalsReturnsFalse()
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
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->calcPerms($input['parentPageRow'])->willReturn(Permission::ALL);
        $this->beUserProphecy->recordEditAccessInternals($input['tableName'], Argument::cetera())->willReturn(false);

        $this->expectException(AccessDeniedEditInternalsException::class);
        $this->expectExceptionCode(1437687404);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForNewContentRecordWithoutPermissions()
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'new',
            'vanillaUid' => 123,
            'parentPageRow' => [
                'uid' => 123,
                'pid' => 321,
            ],
        ];
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->calcPerms($input['parentPageRow'])->willReturn(Permission::NOTHING);

        $this->expectException(AccessDeniedContentEditException::class);
        $this->expectExceptionCode(1437745759);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForNewPageWithoutPermissions()
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
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->calcPerms($input['parentPageRow'])->willReturn(Permission::NOTHING);

        $this->expectException(AccessDeniedPageNewException::class);
        $this->expectExceptionCode(1437745640);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfHookDeniesAccess()
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
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->calcPerms($input['parentPageRow'])->willReturn(Permission::ALL);
        $this->beUserProphecy->recordEditAccessInternals($input['tableName'], Argument::cetera())->willReturn(true);

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/alt_doc.php']['makeEditForm_accessCheck'] = [
            'unitTest' => function () {
                return false;
            }
        ];

        $this->expectException(AccessDeniedHookException::class);
        $this->expectExceptionCode(1437689705);

        (new DatabaseUserPermissionCheck())->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsUserPermissionsOnPageForNewPageIfPageNewIsDeniedAndHookAllowsAccess()
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
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->calcPerms($input['parentPageRow'])->willReturn(Permission::CONTENT_EDIT);
        $this->beUserProphecy->recordEditAccessInternals($input['tableName'], Argument::cetera())->willReturn(true);

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/alt_doc.php']['makeEditForm_accessCheck'] = [
            'unitTest' => function () {
                return true;
            }
        ];

        $result = (new DatabaseUserPermissionCheck())->addData($input);

        self::assertSame(Permission::CONTENT_EDIT, $result['userPermissionOnPage']);
    }

    /**
     * @test
     */
    public function addDataSetsUserPermissionsOnPageForNewPage()
    {
        $input = [
            'tableName' => 'pages',
            'command' => 'new',
            'vanillaUid' => 123,
            'parentPageRow' => [
                'uid' => 123,
                'pid' => 321,
            ],
        ];
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->calcPerms($input['parentPageRow'])->willReturn(Permission::PAGE_NEW);
        $this->beUserProphecy->recordEditAccessInternals($input['tableName'], Argument::cetera())->willReturn(true);

        $result = (new DatabaseUserPermissionCheck())->addData($input);

        self::assertSame(Permission::PAGE_NEW, $result['userPermissionOnPage']);
    }

    /**
     * @test
     */
    public function addDataSetsUserPermissionsOnPageForNewContentRecord()
    {
        $input = [
            'tableName' => 'tt_content',
            'command' => 'new',
            'vanillaUid' => 123,
            'parentPageRow' => [
                'uid' => 123,
                'pid' => 321,
            ],
        ];
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->calcPerms($input['parentPageRow'])->willReturn(Permission::CONTENT_EDIT);
        $this->beUserProphecy->recordEditAccessInternals($input['tableName'], Argument::cetera())->willReturn(true);

        $result = (new DatabaseUserPermissionCheck())->addData($input);

        self::assertSame(Permission::CONTENT_EDIT, $result['userPermissionOnPage']);
    }

    /**
     * @test
     */
    public function addDataSetsPermissionsToAllIfRootLevelRestrictionForTableIsIgnoredForNewContentRecord()
    {
        $input = [
            'tableName' => 'pages',
            'command' => 'new',
            'vanillaUid' => 123,
            'parentPageRow' => null,
        ];
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->recordEditAccessInternals($input['tableName'], Argument::cetera())->willReturn(true);
        $GLOBALS['TCA'][$input['tableName']]['ctrl']['security']['ignoreRootLevelRestriction'] = true;

        $result = (new DatabaseUserPermissionCheck())->addData($input);

        self::assertSame(Permission::ALL, $result['userPermissionOnPage']);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForNewRecordsOnRootLevelWithoutPermissions()
    {
        $input = [
            'tableName' => 'pages',
            'command' => 'new',
            'vanillaUid' => 123,
            'parentPageRow' => null,
        ];

        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);

        $this->expectException(AccessDeniedRootNodeException::class);
        $this->expectExceptionCode(1437745221);

        (new DatabaseUserPermissionCheck())->addData($input);
    }
}
