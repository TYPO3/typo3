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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests related to DataHandler setting proper page permissions
 */
final class PagePermissionTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_defaultpagetsconfig',
    ];

    protected array $configurationToUseInTestInstance = [
        'BE' => [
            'defaultPermissions' => [
                'user' => 'show,editcontent,edit,delete',
                'group' => 'show,editcontent,new',
                'everybody' => 'show',
            ],
        ],
    ];

    private BackendUserAuthentication $backendUser;
    private ActionService $actionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->backendUser = $this->setUpBackendUser(1);
        $this->actionService = new ActionService();
        Bootstrap::initializeLanguageObject();
    }

    /**
     * @test
     */
    public function newPageReceivesDefaultPermissionSet(): void
    {
        $this->backendUser->user['uid'] = 13;
        $this->backendUser->firstMainGroup = 14;
        // Defaults from ext:test_defaultpagetsconfig/Configuration/page.tsconfig do not kick in, it's not below page 88
        $record = $this->insertPage(1);
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
        // Defaults from ext:test_defaultpagetsconfig/Configuration/page.tsconfig kick in here for pages below 88
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
        // Defaults from ext:test_defaultpagetsconfig/Configuration/page.tsconfig kick in here for pages below 88
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
        $this->actionService->modifyRecord(
            'pages',
            (int)$parent['uid'],
            [
                'perms_userid' => 1,
                'perms_groupid' => 1,
                'perms_user' => Permission::PAGE_SHOW,
                'perms_group' => Permission::PAGE_SHOW,
                'perms_everybody' => Permission::PAGE_SHOW,
            ]
        );

        // Insert second page which should inherit settings from page 88
        $record = $this->insertPage((int)$parent['uid']);

        self::assertEquals(1, $record['perms_userid']);
        self::assertEquals(1, $record['perms_groupid']);
        self::assertEquals(Permission::PAGE_SHOW, $record['perms_user']);
        self::assertEquals(Permission::PAGE_SHOW, $record['perms_group']);
        self::assertEquals(Permission::PAGE_SHOW, $record['perms_everybody']);
    }

    private function insertPage(int $pageId = 88, array $fields = ['title' => 'Test page']): array
    {
        // pid 88 comes from ImportDefault
        $result = $this->actionService->createNewRecord('pages', $pageId, $fields);
        $recordUid = $result['pages'][0];
        return BackendUtility::getRecord('pages', $recordUid);
    }
}
