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

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\IpLocker;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BackendUserAuthenticationTest extends UnitTestCase
{
    use ProphecyTrait;

    protected array $defaultFilePermissions = [
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

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '';
    }

    /**
     * Tear down
     */
    protected function tearDown(): void
    {
        FormProtectionFactory::purgeInstances();
        parent::tearDown();
    }

    /////////////////////////////////////////
    // Tests concerning the form protection
    /////////////////////////////////////////
    /**
     * @test
     */
    public function logoffCleansFormProtectionIfBackendUserIsLoggedIn(): void
    {
        $connection = $this->prophesize(Connection::class);
        $connection->delete('sys_lockedrecords', Argument::cetera())->willReturn(1);

        $connectionPool = $this->prophesize(ConnectionPool::class);
        $connectionPool->getConnectionForTable(Argument::cetera())->willReturn($connection->reveal());

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());

        $formProtection = $this->prophesize(BackendFormProtection::class);
        $formProtection->clean()->shouldBeCalled();

        FormProtectionFactory::set(
            'default',
            $formProtection->reveal()
        );

        $sessionBackend = $this->prophesize(SessionBackendInterface::class);
        $sessionBackend->remove(Argument::cetera())->willReturn(true);
        $userSessionManager = new UserSessionManager(
            $sessionBackend->reveal(),
            86400,
            new IpLocker(0, 0),
            'BE'
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

    /**
     * @return array
     */
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
     * @param array $userTsConfiguration
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

        $expectedPermissions = array_merge($this->defaultFilePermissions, $userTsConfiguration);
        array_walk(
            $expectedPermissions,
            static function (&$value) {
                $value = (bool)$value;
            }
        );

        self::assertEquals($expectedPermissions, $subject->getFilePermissions());
    }

    /**
     * @return array
     */
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
                    'recursivedeleteFolder' => 0,
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
                    'recursivedeleteFolder' => 0,
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
     * @param array $defaultPermissions
     * @param int $storageUid
     * @param array $storagePermissions
     * @param array $expectedPermissions
     * @test
     * @dataProvider getFilePermissionsFromStorageDataProvider
     */
    public function getFilePermissionsFromStorageOverwritesDefaultPermissions(array $defaultPermissions, $storageUid, array $storagePermissions, array $expectedPermissions): void
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

    /**
     * @return array
     */
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
     *
     * @param string $permissionValue
     * @param array $expectedPermissions
     *
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
     *
     * @param int $jsConfirmation
     * @param int $typeChangeAllowed
     * @param int $copyMovePasteAllowed
     * @param int $deleteAllowed
     * @param int $feEditAllowed
     * @param int $otherAllowed
     */
    public function jsConfirmationAllowsUnsettingBitsInValue($jsConfirmation, $typeChangeAllowed, $copyMovePasteAllowed, $deleteAllowed, $feEditAllowed, $otherAllowed): void
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

    /**
     * @return array
     */
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
     * cares for non-admin users
     */
    public function getPagePermissionsClauseWithValidNonAdminUserDataProvider(): array
    {
        return [
            'for user' => [
                'perms' => 2,
                'groups' => [],
                'expected' => ' ((`pages`.`perms_everybody` & 2 = 2) OR' .
                ' ((`pages`.`perms_userid` = 123) AND (`pages`.`perms_user` & 2 = 2)))',
            ],
            'for user with groups' => [
                'perms' => 8,
                'groups' => [1, 2],
                'expected' => ' ((`pages`.`perms_everybody` & 8 = 8) OR' .
                ' ((`pages`.`perms_userid` = 123) AND (`pages`.`perms_user` & 8 = 8))' .
                ' OR ((`pages`.`perms_groupid` IN (1, 2)) AND (`pages`.`perms_group` & 8 = 8)))',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getPagePermissionsClauseWithValidNonAdminUserDataProvider
     */
    public function getPagePermissionsClauseWithValidUser(int $perms, array $groups, string $expected): void
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('getDatabasePlatform')->willReturn(new MockPlatform());
        $connectionMock->method('quoteIdentifier')
            ->willReturnCallback(fn(string $identifier): string => '`' . str_replace('.', '`.`', $identifier) . '`');

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->method('expr')->willReturn(
            new ExpressionBuilder($connectionMock)
        );

        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->method('getQueryBuilderForTable')->with('pages')->willReturn($queryBuilderMock);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        $subject = new BackendUserAuthentication();
        $subject->setLogger(new NullLogger());
        $subject->user = ['uid' => 123];
        $subject->userGroupsUID = $groups;

        self::assertEquals($expected, $subject->getPagePermsClause($perms));
    }

    /**
     * Data provider to test page permissions constraints
     * cares for privileged (admin) users
     */
    public static function getPagePermissionsClauseWithValidAdminUserDataProvider(): array
    {
        return [
            'for admin' => [
                'perms' => 1,
                'groups' => [],
                'expected' => ' 1=1',
            ],
            'for admin with groups' => [
                'perms' => 11,
                'groups' => [1, 2],
                'expected' => ' 1=1',
            ],

        ];
    }

    /**
     * @test
     * @dataProvider getPagePermissionsClauseWithValidAdminUserDataProvider
     */
    public function getPagePermissionsClauseWithValidAdminUser(int $perms, array $groups, string $expected): void
    {
        $subject = new BackendUserAuthentication();
        $subject->setLogger(new NullLogger());
        $subject->user = ['uid' => 123, 'admin' => 1];
        $subject->userGroupsUID = $groups;

        self::assertEquals($expected, $subject->getPagePermsClause($perms));
    }

    /**
     * @test
     * @dataProvider checkAuthModeReturnsExpectedValueDataProvider
     * @param string $theValue
     * @param string $authMode
     * @param bool $expectedResult
     */
    public function checkAuthModeReturnsExpectedValue(string $theValue, string $authMode, bool $expectedResult): void
    {
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isAdmin'])
            ->getMock();

        $subject
            ->method('isAdmin')
            ->willReturn(false);

        $subject->groupData['explicit_allowdeny'] =
            'dummytable:dummyfield:explicitly_allowed_value:ALLOW,'
            . 'dummytable:dummyfield:explicitly_denied_value:DENY';

        $result = $subject->checkAuthMode('dummytable', 'dummyfield', $theValue, $authMode);
        self::assertEquals($expectedResult, $result);
    }

    public function checkAuthModeReturnsExpectedValueDataProvider(): array
    {
        return [
            'explicit allow, not allowed value' => [
                'non_allowed_field',
                'explicitAllow',
                false,
            ],
            'explicit allow, allowed value' => [
                'explicitly_allowed_value',
                'explicitAllow',
                true,
            ],
            'explicit deny, not denied value' => [
                'non_denied_field',
                'explicitDeny',
                true,
            ],
            'explicit deny, denied value' => [
                'explicitly_denied_value',
                'explicitDeny',
                false,
            ],
            'invalid value colon' => [
                'containing:invalid:chars',
                'does not matter',
                false,
            ],
            'invalid value comma' => [
                'containing,invalid,chars',
                'does not matter',
                false,
            ],
            'blank value' => [
                '',
                'does not matter',
                true,
            ],
            'divider' => [
                '--div--',
                'explicitAllow',
                true,
            ],
        ];
    }
}
