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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Regular;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

/**
 * Tests related to DataHandler setting proper page permissions
 */
class PagePermissionTest extends AbstractDataHandlerActionTestCase
{
    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/Regular/DataSet/';

    protected $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importScenarioDataSet('ImportDefault');
    }

    /**
     * @test
     */
    public function newPageReceivesDefaultPermissionSet(): void
    {
        $this->backendUser->user['uid'] = 13;
        $this->backendUser->firstMainGroup = 14;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPermissions'] = [
            'user' => 'show,editcontent,edit,delete',
            'group' => 'show,editcontent,new',
            'everybody' => 'show',
        ];
        $record = $this->insertPage();
        self::assertEquals(13, $record['perms_userid']);
        self::assertEquals(14, $record['perms_groupid']);
        self::assertEquals(Permission::PAGE_SHOW + Permission::CONTENT_EDIT + Permission::PAGE_EDIT + Permission::PAGE_DELETE, $record['perms_user']);
        self::assertEquals(Permission::PAGE_SHOW + Permission::CONTENT_EDIT + Permission::PAGE_NEW, $record['perms_group']);
        self::assertEquals(Permission::PAGE_SHOW, $record['perms_everybody']);
    }

    /**
     * @test
     */
    public function newPageReceivesOverriddenPageTsPermissionSet(): void
    {
        $this->backendUser->user['uid'] = 13;
        $this->backendUser->firstMainGroup = 14;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPermissions'] = [
            'user' => 'show,editcontent,edit,delete',
            'group' => 'show,editcontent,new',
            'everybody' => 'show',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'] = '
TCEMAIN.permissions.userid = 12
TCEMAIN.permissions.groupid = 42
TCEMAIN.permissions.user = show,edit
TCEMAIN.permissions.group = show,delete
TCEMAIN.permissions.everybody = show,delete
';
        $record = $this->insertPage();
        self::assertEquals(12, $record['perms_userid']);
        self::assertEquals(42, $record['perms_groupid']);
        self::assertEquals(Permission::PAGE_SHOW + Permission::PAGE_EDIT, $record['perms_user']);
        self::assertEquals(Permission::PAGE_SHOW + Permission::PAGE_DELETE, $record['perms_group']);
        self::assertEquals(Permission::PAGE_SHOW + Permission::PAGE_DELETE, $record['perms_everybody']);
    }

    /**
     * @test
     */
    public function newPageReceivesOverriddenPageTsPermissionSetFromParent()
    {
        $this->backendUser->user['uid'] = 13;
        $this->backendUser->firstMainGroup = 14;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPermissions'] = [
            'user' => 'show,editcontent,edit,delete',
            'group' => 'show,editcontent,new',
            'everybody' => 'show',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'] = '
TCEMAIN.permissions.userid = 12
TCEMAIN.permissions.groupid = 42
TCEMAIN.permissions.user = show,edit
TCEMAIN.permissions.group = show,delete
TCEMAIN.permissions.everybody = show,delete
';
        $parent = $this->insertPage(88, [
            'title' => 'Test page',
            'TSconfig' => '
TCEMAIN.permissions.userid = copyFromParent
TCEMAIN.permissions.groupid = copyFromParent
TCEMAIN.permissions.user = copyFromParent
TCEMAIN.permissions.group = copyFromParent
TCEMAIN.permissions.everybody = copyFromParent
            ',
        ]);

        // We change perm settings of recently added page, so we can really check
        // if perm settings are copied from parent page and not using default settings.
        $this->changePageData(
            (int)$parent['uid'],
            [
                'perms_userid' => 1,
                'perms_groupid' => 1,
                'perms_user' => Permission::PAGE_SHOW,
                'perms_group' => Permission::PAGE_SHOW,
                'perms_everybody' => Permission::PAGE_SHOW,
            ]
        );

        // insert second page which should inherit settings from page 88
        $record = $this->insertPage((int)$parent['uid']);

        self::assertEquals(1, $record['perms_userid']);
        self::assertEquals(1, $record['perms_groupid']);
        self::assertEquals(Permission::PAGE_SHOW, $record['perms_user']);
        self::assertEquals(Permission::PAGE_SHOW, $record['perms_group']);
        self::assertEquals(Permission::PAGE_SHOW, $record['perms_everybody']);
    }

    /**
     * @return array
     */
    protected function insertPage(int $pageId = 88, array $fields = ['title' => 'Test page'])
    {
        // pid 88 comes from ImportDefault
        $result = $this->actionService->createNewRecord('pages', $pageId, $fields);
        $recordUid = $result['pages'][0];
        return BackendUtility::getRecord('pages', $recordUid);
    }

    protected function changePageData(int $pageId, array $data): void
    {
        $this->actionService->modifyRecord(
            'pages',
            $pageId,
            $data
        );
    }
}
