<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

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
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

/**
 * Test case
 */
class DatabaseUserPermissionCheckTest extends UnitTestCase
{
    /**
     * @var DatabaseUserPermissionCheck
     */
    protected $subject;

    /**
     * @var BackendUserAuthentication | ObjectProphecy
     */
    protected $beUserProphecy;

    protected function setUp()
    {
        $this->subject = new DatabaseUserPermissionCheck();

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

        $result = $this->subject->addData([]);

        $this->assertSame(Permission::ALL, $result['userPermissionOnPage']);
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

        $this->setExpectedException(AccessDeniedTableModifyException::class, $this->anything(), 1437683248);

        $this->subject->addData($input);
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
                'pid' => 321,
            ],
        ];
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->calcPerms(['pid' => 321])->willReturn(Permission::NOTHING);

        $this->setExpectedException(AccessDeniedContentEditException::class, 1437679657);

        $this->subject->addData($input);
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
            'parentPageRow' => [
                'pid' => 321,
            ],
        ];
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->calcPerms(['pid' => 321])->willReturn(Permission::CONTENT_EDIT);
        $this->beUserProphecy->recordEditAccessInternals($input['tableName'], Argument::any())->willReturn(true);

        $result = $this->subject->addData($input);

        $this->assertSame(Permission::CONTENT_EDIT, $result['userPermissionOnPage']);
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

        $this->setExpectedException(AccessDeniedPageEditException::class, 1437679336);

        $this->subject->addData($input);
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
                'pid' => 321
            ],
        ];
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->calcPerms($input['databaseRow'])->willReturn(Permission::PAGE_EDIT);
        $this->beUserProphecy->recordEditAccessInternals($input['tableName'], Argument::cetera())->willReturn(true);

        $result = $this->subject->addData($input);

        $this->assertSame(Permission::PAGE_EDIT, $result['userPermissionOnPage']);
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

        $result = $this->subject->addData($input);

        $this->assertSame(Permission::ALL, $result['userPermissionOnPage']);
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

        $this->setExpectedException(AccessDeniedRootNodeException::class, $this->anything(), 1437679856);

        $this->subject->addData($input);
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
            'parentPageRow' => [
                'uid' => 123,
                'pid' => 321,
            ],
        ];
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->calcPerms($input['parentPageRow'])->willReturn(Permission::ALL);
        $this->beUserProphecy->recordEditAccessInternals($input['tableName'], Argument::cetera())->willReturn(false);

        $this->setExpectedException(AccessDeniedEditInternalsException::class, $this->anything(), 1437687404);

        $this->subject->addData($input);
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

        $this->setExpectedException(AccessDeniedContentEditException::class, $this->anything(), 1437745759);

        $this->subject->addData($input);
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
            'parentPageRow' => [
                'uid' => 123,
                'pid' => 321,
            ],
        ];
        $this->beUserProphecy->isAdmin()->willReturn(false);
        $this->beUserProphecy->check('tables_modify', $input['tableName'])->willReturn(true);
        $this->beUserProphecy->calcPerms($input['parentPageRow'])->willReturn(Permission::NOTHING);

        $this->setExpectedException(AccessDeniedPageNewException::class, $this->anything(), 1437745640);

        $this->subject->addData($input);
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

        $this->setExpectedException(AccessDeniedHookException::class, $this->anything(), 1437689705);

        $this->subject->addData($input);
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

        $result = $this->subject->addData($input);

        $this->assertSame(Permission::CONTENT_EDIT, $result['userPermissionOnPage']);
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

        $result = $this->subject->addData($input);

        $this->assertSame(Permission::PAGE_NEW, $result['userPermissionOnPage']);
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

        $result = $this->subject->addData($input);

        $this->assertSame(Permission::CONTENT_EDIT, $result['userPermissionOnPage']);
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

        $result = $this->subject->addData($input);

        $this->assertSame(Permission::ALL, $result['userPermissionOnPage']);
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

        $this->setExpectedException(AccessDeniedRootNodeException::class, $this->anything(), 1437745221);

        $this->subject->addData($input);
    }
}
