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

namespace TYPO3\CMS\Core\Tests\Unit\Authentication;

use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\IpLocker;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class BackendUserAuthenticationTest extends UnitTestCase
{
    public function tearDown(): void
    {
        FormProtectionFactory::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function logoffCleansFormProtectionIfBackendUserIsLoggedIn(): void
    {
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('delete')->with('sys_lockedrecords', self::anything())->willReturn(1);

        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->method('getConnectionForTable')->with(self::anything())->willReturn($connectionMock);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        $formProtectionMock = $this->createMock(BackendFormProtection::class);
        $formProtectionMock->expects(self::once())->method('clean');

        $formProtectionFactory = new FormProtectionFactory(
            $this->createMock(FlashMessageService::class),
            $this->createMock(LanguageServiceFactory::class),
            $this->createMock(Registry::class)
        );
        GeneralUtility::addInstance(FormProtectionFactory::class, $formProtectionFactory);
        GeneralUtility::addInstance(BackendFormProtection::class, $formProtectionMock);

        $sessionBackendMock = $this->createMock(SessionBackendInterface::class);
        $sessionBackendMock->method('remove')->with(self::anything())->willReturn(true);
        $userSessionManager = new UserSessionManager(
            $sessionBackendMock,
            86400,
            new IpLocker(0, 0)
        );

        $GLOBALS['BE_USER'] = $this->getMockBuilder(BackendUserAuthentication::class)->getMock();
        $GLOBALS['BE_USER']->user = [
            'uid' => 4711,
        ];
        $GLOBALS['BE_USER']->setLogger(new NullLogger());
        $GLOBALS['BE_USER']->initializeUserSessionManager($userSessionManager);

        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->addMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();

        $subject->setLogger(new NullLogger());
        $subject->initializeUserSessionManager($userSessionManager);
        $subject->logoff();
    }

    public function getFilePermissionsTakesUserDefaultAndStoragePermissionsIntoAccountIfUserIsNotAdminDataProvider(): array
    {
        return [
            'Only read permissions' => [
                [
                    'addFile' => 0,
                    'readFile' => 1,
                    'writeFile' => 0,
                    'copyFile' => 0,
                    'moveFile' => 0,
                    'renameFile' => 0,
                    'deleteFile' => 0,
                    'addFolder' => 0,
                    'readFolder' => 1,
                    'copyFolder' => 0,
                    'moveFolder' => 0,
                    'renameFolder' => 0,
                    'writeFolder' => 0,
                    'deleteFolder' => 0,
                    'recursivedeleteFolder' => 0,
                ],
            ],
            'Uploading allowed' => [
                [
                    'addFile' => 1,
                    'readFile' => 1,
                    'writeFile' => 1,
                    'copyFile' => 1,
                    'moveFile' => 1,
                    'renameFile' => 1,
                    'deleteFile' => 1,
                    'addFolder' => 0,
                    'readFolder' => 1,
                    'copyFolder' => 0,
                    'moveFolder' => 0,
                    'renameFolder' => 0,
                    'writeFolder' => 0,
                    'deleteFolder' => 0,
                    'recursivedeleteFolder' => 0,
                ],
            ],
            'One value is enough' => [
                [
                    'addFile' => 1,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getFilePermissionsTakesUserDefaultAndStoragePermissionsIntoAccountIfUserIsNotAdminDataProvider
     */
    public function getFilePermissionsTakesUserDefaultPermissionsFromTsConfigIntoAccountIfUserIsNotAdmin(array $userTsConfiguration): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['isAdmin', 'getTSConfig'])
            ->getMock();

        $subject
            ->method('isAdmin')
            ->willReturn(false);

        $subject->setLogger(new NullLogger());
        $subject
            ->method('getTSConfig')
            ->willReturn([
                'permissions.' => [
                    'file.' => [
                        'default.' => $userTsConfiguration,
                    ],
                ],
            ]);
        $defaultFilePermissions = [
            // File permissions
            'addFile' => false,
            'readFile' => false,
            'writeFile' => false,
            'copyFile' => false,
            'moveFile' => false,
            'renameFile' => false,
            'deleteFile' => false,
            // Folder permissions
            'addFolder' => false,
            'readFolder' => false,
            'writeFolder' => false,
            'copyFolder' => false,
            'moveFolder' => false,
            'renameFolder' => false,
            'deleteFolder' => false,
            'recursivedeleteFolder' => false,
        ];
        $expectedPermissions = array_merge($defaultFilePermissions, $userTsConfiguration);
        array_walk(
            $expectedPermissions,
            static function (&$value) {
                $value = (bool)$value;
            }
        );

        self::assertEquals($expectedPermissions, $subject->getFilePermissions());
    }

    public function getFilePermissionsFromStorageDataProvider(): array
    {
        $defaultPermissions = [
            'addFile' => true,
            'readFile' => true,
            'writeFile' => true,
            'copyFile' => true,
            'moveFile' => true,
            'renameFile' => true,
            'deleteFile' => true,
            'addFolder' => true,
            'readFolder' => true,
            'copyFolder' => true,
            'moveFolder' => true,
            'renameFolder' => true,
            'writeFolder' => true,
            'deleteFolder' => true,
            'recursivedeleteFolder' => true,
        ];

        return [
            'Overwrites given storage permissions with default permissions' => [
                $defaultPermissions,
                1,
                [
                    'addFile' => 0,
                    'recursivedeleteFolder' =>0,
                ],
                [
                    'addFile' => 0,
                    'readFile' => 1,
                    'writeFile' => 1,
                    'copyFile' => 1,
                    'moveFile' => 1,
                    'renameFile' => 1,
                    'deleteFile' => 1,
                    'addFolder' => 1,
                    'readFolder' => 1,
                    'copyFolder' => 1,
                    'moveFolder' => 1,
                    'renameFolder' => 1,
                    'writeFolder' => 1,
                    'deleteFolder' => 1,
                    'recursivedeleteFolder' => 0,
                ],
            ],
            'Overwrites given storage 0 permissions with default permissions' => [
                $defaultPermissions,
                0,
                [
                    'addFile' => 0,
                    'recursivedeleteFolder' =>0,
                ],
                [
                    'addFile' => false,
                    'readFile' => true,
                    'writeFile' => true,
                    'copyFile' => true,
                    'moveFile' => true,
                    'renameFile' => true,
                    'deleteFile' => true,
                    'addFolder' => true,
                    'readFolder' => true,
                    'copyFolder' => true,
                    'moveFolder' => true,
                    'renameFolder' => true,
                    'writeFolder' => true,
                    'deleteFolder' => true,
                    'recursivedeleteFolder' => false,
                ],
            ],
            'Returns default permissions if no storage permissions are found' => [
                $defaultPermissions,
                1,
                [],
                [
                    'addFile' => true,
                    'readFile' => true,
                    'writeFile' => true,
                    'copyFile' => true,
                    'moveFile' => true,
                    'renameFile' => true,
                    'deleteFile' => true,
                    'addFolder' => true,
                    'readFolder' => true,
                    'copyFolder' => true,
                    'moveFolder' => true,
                    'renameFolder' => true,
                    'writeFolder' => true,
                    'deleteFolder' => true,
                    'recursivedeleteFolder' => true,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getFilePermissionsFromStorageDataProvider
     */
    public function getFilePermissionsFromStorageOverwritesDefaultPermissions(array $defaultPermissions, int $storageUid, array $storagePermissions, array $expectedPermissions): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['isAdmin', 'getFilePermissions', 'getTSConfig'])
            ->getMock();
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn($storageUid);

        $subject
            ->method('isAdmin')
            ->willReturn(false);

        $subject
            ->method('getFilePermissions')
            ->willReturn($defaultPermissions);

        $subject
            ->method('getTSConfig')
            ->willReturn([
                'permissions.' => [
                    'file.' => [
                        'storage.' => [
                            $storageUid . '.' => $storagePermissions,
                        ],
                    ],
                ],
            ]);

        self::assertEquals($expectedPermissions, $subject->getFilePermissionsForStorage($storageMock));
    }

    /**
     * @test
     * @dataProvider getFilePermissionsFromStorageDataProvider
     */
    public function getFilePermissionsFromStorageAlwaysReturnsDefaultPermissionsForAdmins(array $defaultPermissions, int $storageUid, array $storagePermissions): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['isAdmin', 'getFilePermissions', 'getTSConfig'])
            ->getMock();
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->method('getUid')->willReturn($storageUid);

        $subject
            ->method('isAdmin')
            ->willReturn(true);

        $subject
            ->method('getFilePermissions')
            ->willReturn($defaultPermissions);

        $subject
            ->method('getTSConfig')
            ->willReturn([
                'permissions.' => [
                    'file.' => [
                        'storage.' => [
                            $storageUid . '.' => $storagePermissions,
                        ],
                    ],
                ],
            ]);

        self::assertEquals($defaultPermissions, $subject->getFilePermissionsForStorage($storageMock));
    }

    public function getFilePermissionsTakesUserDefaultPermissionsFromRecordIntoAccountIfUserIsNotAdminDataProvider(): array
    {
        return [
            'No permission' => [
                '',
                [
                    'addFile' => false,
                    'readFile' => false,
                    'writeFile' => false,
                    'copyFile' => false,
                    'moveFile' => false,
                    'renameFile' => false,
                    'deleteFile' => false,
                    'addFolder' => false,
                    'readFolder' => false,
                    'copyFolder' => false,
                    'moveFolder' => false,
                    'renameFolder' => false,
                    'writeFolder' => false,
                    'deleteFolder' => false,
                    'recursivedeleteFolder' => false,
                ],
            ],
            'Standard file permissions' => [
                'addFile,readFile,writeFile,copyFile,moveFile,renameFile,deleteFile',
                [
                    'addFile' => true,
                    'readFile' => true,
                    'writeFile' => true,
                    'copyFile' => true,
                    'moveFile' => true,
                    'renameFile' => true,
                    'deleteFile' => true,
                    'addFolder' => false,
                    'readFolder' => false,
                    'copyFolder' => false,
                    'moveFolder' => false,
                    'renameFolder' => false,
                    'writeFolder' => false,
                    'deleteFolder' => false,
                    'recursivedeleteFolder' => false,
                ],
            ],
            'Standard folder permissions' => [
                'addFolder,readFolder,moveFolder,renameFolder,writeFolder,deleteFolder',
                [
                    'addFile' => false,
                    'readFile' => false,
                    'writeFile' => false,
                    'copyFile' => false,
                    'moveFile' => false,
                    'renameFile' => false,
                    'deleteFile' => false,
                    'addFolder' => true,
                    'readFolder' => true,
                    'writeFolder' => true,
                    'copyFolder' => false,
                    'moveFolder' => true,
                    'renameFolder' => true,
                    'deleteFolder' => true,
                    'recursivedeleteFolder' => false,
                ],
            ],
            'Copy folder allowed' => [
                'readFolder,copyFolder',
                [
                    'addFile' => false,
                    'readFile' => false,
                    'writeFile' => false,
                    'copyFile' => false,
                    'moveFile' => false,
                    'renameFile' => false,
                    'deleteFile' => false,
                    'addFolder' => false,
                    'readFolder' => true,
                    'writeFolder' => false,
                    'copyFolder' => true,
                    'moveFolder' => false,
                    'renameFolder' => false,
                    'deleteFolder' => false,
                    'recursivedeleteFolder' => false,
                ],
            ],
            'Copy folder and remove subfolders allowed' => [
                'readFolder,copyFolder,recursivedeleteFolder',
                [
                    'addFile' => false,
                    'readFile' => false,
                    'writeFile' => false,
                    'copyFile' => false,
                    'moveFile' => false,
                    'renameFile' => false,
                    'deleteFile' => false,
                    'addFolder' => false,
                    'readFolder' => true,
                    'writeFolder' => false,
                    'copyFolder' => true,
                    'moveFolder' => false,
                    'renameFolder' => false,
                    'deleteFolder' => false,
                    'recursivedeleteFolder' => true,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getFilePermissionsTakesUserDefaultPermissionsFromRecordIntoAccountIfUserIsNotAdminDataProvider
     */
    public function getFilePermissionsTakesUserDefaultPermissionsFromRecordIntoAccountIfUserIsNotAdmin(string $permissionValue, array $expectedPermissions): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['isAdmin', 'getTSConfig'])
            ->getMock();

        $subject
            ->method('isAdmin')
            ->willReturn(false);

        $subject
            ->method('getTSConfig')
            ->willReturn([]);
        $subject->groupData['file_permissions'] = $permissionValue;
        self::assertEquals($expectedPermissions, $subject->getFilePermissions());
    }

    /**
     * @test
     */
    public function getFilePermissionsGrantsAllPermissionsToAdminUsers(): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['isAdmin'])
            ->getMock();

        $subject
            ->method('isAdmin')
            ->willReturn(true);

        $expectedPermissions = [
            'addFile' => true,
            'readFile' => true,
            'writeFile' => true,
            'copyFile' => true,
            'moveFile' => true,
            'renameFile' => true,
            'deleteFile' => true,
            'addFolder' => true,
            'readFolder' => true,
            'writeFolder' => true,
            'copyFolder' => true,
            'moveFolder' => true,
            'renameFolder' => true,
            'deleteFolder' => true,
            'recursivedeleteFolder' => true,
        ];

        self::assertEquals($expectedPermissions, $subject->getFilePermissions());
    }

    /**
     * @test
     */
    public function jsConfirmationReturnsTrueIfPassedValueEqualsConfiguration(): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['getTSConfig'])
            ->getMock();
        $subject->method('getTSConfig')->with()->willReturn([
            'options.' => [
                'alertPopups' => 1,
            ],
        ]);
        self::assertTrue($subject->jsConfirmation(JsConfirmation::TYPE_CHANGE));
        self::assertFalse($subject->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE));
    }

    /**
     * @test
     */
    public function jsConfirmationAllowsSettingMultipleBitsInValue(): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['getTSConfig'])
            ->getMock();
        $subject->method('getTSConfig')->with()->willReturn([
            'options.' => [
                'alertPopups' => 3,
            ],
        ]);
        self::assertTrue($subject->jsConfirmation(JsConfirmation::TYPE_CHANGE));
        self::assertTrue($subject->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE));
    }

    /**
     * @test
     * @dataProvider jsConfirmationsWithUnsetBits
     */
    public function jsConfirmationAllowsUnsettingBitsInValue(int $jsConfirmation, bool $typeChangeAllowed, bool $copyMovePasteAllowed, bool $deleteAllowed, bool $feEditAllowed, bool $otherAllowed): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['getTSConfig'])
            ->getMock();
        $subject->method('getTSConfig')->with()->willReturn([
            'options.' => [
                'alertPopups' => $jsConfirmation,
            ],
        ]);
        self::assertEquals($typeChangeAllowed, $subject->jsConfirmation(JsConfirmation::TYPE_CHANGE));
        self::assertEquals($copyMovePasteAllowed, $subject->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE));
        self::assertEquals($deleteAllowed, $subject->jsConfirmation(JsConfirmation::DELETE));
        self::assertEquals($feEditAllowed, $subject->jsConfirmation(JsConfirmation::FE_EDIT));
        self::assertEquals($otherAllowed, $subject->jsConfirmation(JsConfirmation::OTHER));
    }

    public function jsConfirmationsWithUnsetBits(): array
    {
        return [
            'All except "type change" and "copy/move/paste"' => [
                252,
                false,
                false,
                true,
                true,
                true,
            ],
            'All except "other"' => [
                127,
                true,
                true,
                true,
                true,
                false,
            ],
        ];
    }

    /**
     * @test
     */
    public function jsConfirmationAlwaysReturnsFalseIfNoConfirmationIsSet(): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['getTSConfig'])
            ->getMock();
        $subject->method('getTSConfig')->with()->willReturn([
            'options.' => [
                'alertPopups' => 0,
            ],
        ]);
        self::assertFalse($subject->jsConfirmation(JsConfirmation::TYPE_CHANGE));
        self::assertFalse($subject->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE));
    }

    /**
     * @test
     */
    public function jsConfirmationReturnsTrueIfConfigurationIsMissing(): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['getTSConfig'])
            ->getMock();

        self::assertTrue($subject->jsConfirmation(JsConfirmation::TYPE_CHANGE));
    }

    /**
     * Data provider to test page permissions constraints
     * returns an array of test conditions:
     *  - permission bit(s) as integer
     *  - admin flag
     *  - groups for user
     *  - expected SQL fragment
     */
    public function getPagePermissionsClauseWithValidUserDataProvider(): array
    {
        return [
            'for admin' => [
                1,
                true,
                [],
                ' 1=1',
            ],
            'for admin with groups' => [
                11,
                true,
                [1, 2],
                ' 1=1',
            ],
            'for user' => [
                2,
                false,
                [],
                ' (((`pages`.`perms_everybody` & 2 = 2) OR' .
                ' (((`pages`.`perms_userid` = 123) AND (`pages`.`perms_user` & 2 = 2)))))',
            ],
            'for user with groups' => [
                8,
                false,
                [1, 2],
                ' (((`pages`.`perms_everybody` & 8 = 8) OR' .
                ' (((`pages`.`perms_userid` = 123) AND (`pages`.`perms_user` & 8 = 8)))' .
                ' OR (((`pages`.`perms_groupid` IN (1, 2)) AND (`pages`.`perms_group` & 8 = 8)))))',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getPagePermissionsClauseWithValidUserDataProvider
     */
    public function getPagePermissionsClauseWithValidUser(int $perms, bool $admin, array $groups, string $expected): void
    {
        // We only need to setup the mocking for the non-admin cases
        // If this setup is done for admin cases the FIFO behavior
        // of GeneralUtility::addInstance will influence other tests
        // as the ConnectionPool is never used!
        if (!$admin) {
            $connectionMock = $this->createMock(Connection::class);
            $connectionMock->method('getDatabasePlatform')->willReturn(new MockPlatform());
            $connectionMock->method('quoteIdentifier')->with(self::anything())
                ->willReturnCallback(fn ($identifier) => '`' . str_replace('.', '`.`', $identifier) . '`');

            $queryBuilderMock = $this->createMock(QueryBuilder::class);
            $queryBuilderMock->method('expr')->willReturn(
                new ExpressionBuilder($connectionMock)
            );

            $connectionPoolMock = $this->createMock(ConnectionPool::class);
            $connectionPoolMock->method('getQueryBuilderForTable')->with('pages')->willReturn($queryBuilderMock);
            // Shift previously added instance
            GeneralUtility::makeInstance(ConnectionPool::class);
            GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);
        }

        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->onlyMethods(['isAdmin'])
            ->getMock();
        $subject->setLogger(new NullLogger());
        $subject
            ->method('isAdmin')
            ->willReturn($admin);

        $subject->user = ['uid' => 123];
        $subject->userGroupsUID = $groups;

        self::assertEquals($expected, $subject->getPagePermsClause($perms));
    }

    /**
     * @test
     * @dataProvider checkAuthModeReturnsExpectedValueDataProvider
     */
    public function checkAuthModeReturnsExpectedValue(string $theValue, bool $expectedResult): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isAdmin'])
            ->getMock();

        $subject
            ->method('isAdmin')
            ->willReturn(false);

        $subject->groupData['explicit_allowdeny'] =
            'dummytable:dummyfield:explicitly_allowed_value,'
            . 'dummytable:dummyfield:explicitly_denied_value';

        $result = $subject->checkAuthMode('dummytable', 'dummyfield', $theValue);
        self::assertEquals($expectedResult, $result);
    }

    public function checkAuthModeReturnsExpectedValueDataProvider(): array
    {
        return [
            'explicit allow, not allowed value' => [
                'non_allowed_field',
                false,
            ],
            'explicit allow, allowed value' => [
                'explicitly_allowed_value',
                true,
            ],
            'invalid value colon' => [
                'containing:invalid:chars',
                false,
            ],
            'invalid value comma' => [
                'containing,invalid,chars',
                false,
            ],
            'blank value' => [
                '',
                true,
            ],
            'divider' => [
                '--div--',
                true,
            ],
        ];
    }
}
