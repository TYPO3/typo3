<?php
namespace TYPO3\CMS\Core\Tests\Unit\Authentication;

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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BackendUserAuthenticationTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $defaultFilePermissions = [
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
        'recursivedeleteFolder' => false
    ];

    /**
     * Tear down
     */
    protected function tearDown()
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
    public function logoffCleansFormProtectionIfBackendUserIsLoggedIn()
    {
        /** @var ObjectProphecy|Connection $connection */
        $connection = $this->prophesize(Connection::class);
        $connection->delete('sys_lockedrecords', Argument::cetera())->willReturn(1);

        /** @var ObjectProphecy|ConnectionPool $connectionPool */
        $connectionPool = $this->prophesize(ConnectionPool::class);
        $connectionPool->getConnectionForTable(Argument::cetera())->willReturn($connection->reveal());

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());

        /** @var ObjectProphecy|Connection $connection */
        $connection = $this->prophesize(Connection::class);
        $connection->delete('be_sessions', Argument::cetera())->willReturn(1);

        /** @var ObjectProphecy|ConnectionPool $connectionPool */
        $connectionPool = $this->prophesize(ConnectionPool::class);
        $connectionPool->getConnectionForTable(Argument::cetera())->willReturn($connection->reveal());

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());

        /** @var ObjectProphecy|\TYPO3\CMS\Core\FormProtection\AbstractFormProtection $formProtection */
        $formProtection = $this->prophesize(\TYPO3\CMS\Core\FormProtection\BackendFormProtection::class);
        $formProtection->clean()->shouldBeCalled();

        FormProtectionFactory::set(
            'default',
            $formProtection->reveal()
        );

        $GLOBALS['BE_USER'] = $this->getMockBuilder(BackendUserAuthentication::class)->getMock();
        $GLOBALS['BE_USER']->user = ['uid' => $this->getUniqueId()];

        /** @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();

        $subject->logoff();
    }

    /**
     * @return array
     */
    public function getTSConfigDataProvider()
    {
        $completeConfiguration = [
            'value' => 'oneValue',
            'value.' => ['oneProperty' => 'oneValue'],
            'permissions.' => [
                'file.' => [
                    'default.' => ['readAction' => '1'],
                    '1.' => ['writeAction' => '1'],
                    '0.' => ['readAction' => '0'],
                ],
            ]
        ];

        return [
            'single level string' => [
                $completeConfiguration,
                'permissions',
                [
                    'value' => null,
                    'properties' =>
                    [
                        'file.' => [
                            'default.' => ['readAction' => '1'],
                            '1.' => ['writeAction' => '1'],
                            '0.' => ['readAction' => '0'],
                        ],
                    ],
                ],
            ],
            'two levels string' => [
                $completeConfiguration,
                'permissions.file',
                [
                    'value' => null,
                    'properties' =>
                    [
                        'default.' => ['readAction' => '1'],
                        '1.' => ['writeAction' => '1'],
                        '0.' => ['readAction' => '0'],
                    ],
                ],
            ],
            'three levels string' => [
                $completeConfiguration,
                'permissions.file.default',
                [
                    'value' => null,
                    'properties' =>
                    ['readAction' => '1'],
                ],
            ],
            'three levels string with integer property' => [
                $completeConfiguration,
                'permissions.file.1',
                [
                    'value' => null,
                    'properties' => ['writeAction' => '1'],
                ],
            ],
            'three levels string with integer zero property' => [
                $completeConfiguration,
                'permissions.file.0',
                [
                    'value' => null,
                    'properties' => ['readAction' => '0'],
                ],
            ],
            'four levels string with integer zero property, value, no properties' => [
                $completeConfiguration,
                'permissions.file.0.readAction',
                [
                    'value' => '0',
                    'properties' => null,
                ],
            ],
            'four levels string with integer property, value, no properties' => [
                $completeConfiguration,
                'permissions.file.1.writeAction',
                [
                    'value' => '1',
                    'properties' => null,
                ],
            ],
            'one level, not existent string' => [
                $completeConfiguration,
                'foo',
                [
                    'value' => null,
                    'properties' => null,
                ],
            ],
            'two level, not existent string' => [
                $completeConfiguration,
                'foo.bar',
                [
                    'value' => null,
                    'properties' => null,
                ],
            ],
            'two level, where second level does not exist' => [
                $completeConfiguration,
                'permissions.bar',
                [
                    'value' => null,
                    'properties' => null,
                ],
            ],
            'three level, where third level does not exist' => [
                $completeConfiguration,
                'permissions.file.foo',
                [
                    'value' => null,
                    'properties' => null,
                ],
            ],
            'three level, where second and third level does not exist' => [
                $completeConfiguration,
                'permissions.foo.bar',
                [
                    'value' => null,
                    'properties' => null,
                ],
            ],
            'value and properties' => [
                $completeConfiguration,
                'value',
                [
                    'value' => 'oneValue',
                    'properties' => ['oneProperty' => 'oneValue'],
                ],
            ],
        ];
    }

    /**
     * @param array $completeConfiguration
     * @param string $objectString
     * @param array $expectedConfiguration
     * @dataProvider getTSConfigDataProvider
     * @test
     */
    public function getTSConfigReturnsCorrectArrayForGivenObjectString(array $completeConfiguration, $objectString, array $expectedConfiguration)
    {
        /** @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $subject->userTS = $completeConfiguration;

        $actualConfiguration = $subject->getTSConfig($objectString);
        $this->assertSame($expectedConfiguration, $actualConfiguration);
    }

    /**
     * @return array
     */
    public function getFilePermissionsTakesUserDefaultAndStoragePermissionsIntoAccountIfUserIsNotAdminDataProvider()
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
                ]
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
                    'recursivedeleteFolder' => 0
                ]
            ],
            'One value is enough' => [
                [
                    'addFile' => 1,
                ]
            ],
        ];
    }

    /**
     * @param array $userTsConfiguration
     * @test
     * @dataProvider getFilePermissionsTakesUserDefaultAndStoragePermissionsIntoAccountIfUserIsNotAdminDataProvider
     */
    public function getFilePermissionsTakesUserDefaultPermissionsFromTsConfigIntoAccountIfUserIsNotAdmin(array $userTsConfiguration)
    {
        /** @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->setMethods(['isAdmin'])
            ->getMock();

        $subject
            ->expects($this->any())
            ->method('isAdmin')
            ->will($this->returnValue(false));

        $subject->userTS = [
            'permissions.' => [
                'file.' => [
                    'default.' => $userTsConfiguration
                ],
            ]
        ];

        $expectedPermissions = array_merge($this->defaultFilePermissions, $userTsConfiguration);
        array_walk(
            $expectedPermissions,
            function (&$value) {
                $value = (bool)$value;
            }
        );

        $this->assertEquals($expectedPermissions, $subject->getFilePermissions());
    }

    /**
     * @return array
     */
    public function getFilePermissionsFromStorageDataProvider()
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
            'recursivedeleteFolder' => true
        ];

        return [
            'Overwrites given storage permissions with default permissions' => [
                $defaultPermissions,
                1,
                [
                    'addFile' => 0,
                    'recursivedeleteFolder' =>0
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
                    'recursivedeleteFolder' => 0
                ]
            ],
            'Overwrites given storage 0 permissions with default permissions' => [
                $defaultPermissions,
                0,
                [
                    'addFile' => 0,
                    'recursivedeleteFolder' =>0
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
                    'recursivedeleteFolder' => false
                ]
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
                    'recursivedeleteFolder' => true
                ]
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
    public function getFilePermissionsFromStorageOverwritesDefaultPermissions(array $defaultPermissions, $storageUid, array $storagePermissions, array $expectedPermissions)
    {
        /** @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->setMethods(['isAdmin', 'getFilePermissions'])
            ->getMock();
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->expects($this->any())->method('getUid')->will($this->returnValue($storageUid));

        $subject
            ->expects($this->any())
            ->method('isAdmin')
            ->will($this->returnValue(false));

        $subject
            ->expects($this->any())
            ->method('getFilePermissions')
            ->will($this->returnValue($defaultPermissions));

        $subject->userTS = [
            'permissions.' => [
                'file.' => [
                    'storage.' => [
                        $storageUid . '.' => $storagePermissions
                    ],
                ],
            ]
        ];

        $this->assertEquals($expectedPermissions, $subject->getFilePermissionsForStorage($storageMock));
    }

    /**
     * @param array $defaultPermissions
     * @param $storageUid
     * @param array $storagePermissions
     * @test
     * @dataProvider getFilePermissionsFromStorageDataProvider
     */
    public function getFilePermissionsFromStorageAlwaysReturnsDefaultPermissionsForAdmins(array $defaultPermissions, $storageUid, array $storagePermissions)
    {
        /** @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->setMethods(['isAdmin', 'getFilePermissions'])
            ->getMock();
        $storageMock = $this->createMock(ResourceStorage::class);
        $storageMock->expects($this->any())->method('getUid')->will($this->returnValue($storageUid));

        $subject
            ->expects($this->any())
            ->method('isAdmin')
            ->will($this->returnValue(true));

        $subject
            ->expects($this->any())
            ->method('getFilePermissions')
            ->will($this->returnValue($defaultPermissions));

        $subject->userTS = [
            'permissions.' => [
                'file.' => [
                    'storage.' => [
                        $storageUid . '.' => $storagePermissions
                    ],
                ],
            ]
        ];

        $this->assertEquals($defaultPermissions, $subject->getFilePermissionsForStorage($storageMock));
    }

    /**
     * @return array
     */
    public function getFilePermissionsTakesUserDefaultPermissionsFromRecordIntoAccountIfUserIsNotAdminDataProvider()
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
                    'recursivedeleteFolder' => false
                ]
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
                    'recursivedeleteFolder' => false
                ]
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
                    'recursivedeleteFolder' => false
                ]
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
                    'recursivedeleteFolder' => false
                ]
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
                    'recursivedeleteFolder' => true
                ]
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
    public function getFilePermissionsTakesUserDefaultPermissionsFromRecordIntoAccountIfUserIsNotAdmin(string $permissionValue, array $expectedPermissions)
    {
        /** @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->setMethods(['isAdmin'])
            ->getMock();

        $subject
            ->expects($this->any())
            ->method('isAdmin')
            ->will($this->returnValue(false));

        $subject->userTS = [];
        $subject->groupData['file_permissions'] = $permissionValue;
        $this->assertEquals($expectedPermissions, $subject->getFilePermissions());
    }

    /**
     * @test
     */
    public function getFilePermissionsGrantsAllPermissionsToAdminUsers()
    {
        /** @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->setMethods(['isAdmin'])
            ->getMock();

        $subject
            ->expects($this->any())
            ->method('isAdmin')
            ->will($this->returnValue(true));

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
            'recursivedeleteFolder' => true
        ];

        $this->assertEquals($expectedPermissions, $subject->getFilePermissions());
    }

    /**
     * @test
     */
    public function jsConfirmationReturnsTrueIfPassedValueEqualsConfiguration()
    {
        /** @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->setMethods(['getTSConfig'])
            ->getMock();
        $subject->method('getTSConfig')->with('options.alertPopups')->willReturn(['value' => 1]);

        $this->assertTrue($subject->jsConfirmation(JsConfirmation::TYPE_CHANGE));
        $this->assertFalse($subject->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE));
    }

    /**
     * @test
     */
    public function jsConfirmationAllowsSettingMultipleBitsInValue()
    {
        /** @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->setMethods(['getTSConfig'])
            ->getMock();
        $subject->method('getTSConfig')->with('options.alertPopups')->willReturn(['value' => 3]);

        $this->assertTrue($subject->jsConfirmation(JsConfirmation::TYPE_CHANGE));
        $this->assertTrue($subject->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE));
    }

    /**
     * @test
     */
    public function jsConfirmationAlwaysReturnsFalseIfNoConfirmationIsSet()
    {
        /** @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->setMethods(['getTSConfig'])
            ->getMock();
        $subject->method('getTSConfig')->with('options.alertPopups')->willReturn(['value' => 0]);

        $this->assertFalse($subject->jsConfirmation(JsConfirmation::TYPE_CHANGE));
        $this->assertFalse($subject->jsConfirmation(JsConfirmation::COPY_MOVE_PASTE));
    }

    /**
     * @test
     */
    public function jsConfirmationReturnsTrueIfConfigurationIsMissing()
    {
        /** @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->setMethods(['getTSConfig'])
            ->getMock();

        $this->assertTrue($subject->jsConfirmation(JsConfirmation::TYPE_CHANGE));
    }

    /**
     * Data provider to test page permissions constraints
     * returns an array of test conditions:
     *  - permission bit(s) as integer
     *  - admin flag
     *  - groups for user
     *  - expected SQL fragment
     *
     * @return array
     */
    public function getPagePermissionsClauseWithValidUserDataProvider(): array
    {
        return [
            'for admin' => [
                1,
                true,
                '',
                ' 1=1'
            ],
            'for admin with groups' => [
                11,
                true,
                '1,2',
                ' 1=1'
            ],
            'for user' => [
                2,
                false,
                '',
                ' ((`pages`.`perms_everybody` & 2 = 2) OR' .
                ' ((`pages`.`perms_userid` = 123) AND (`pages`.`perms_user` & 2 = 2)))'
            ],
            'for user with groups' => [
                8,
                false,
                '1,2',
                ' ((`pages`.`perms_everybody` & 8 = 8) OR' .
                ' ((`pages`.`perms_userid` = 123) AND (`pages`.`perms_user` & 8 = 8))' .
                ' OR ((`pages`.`perms_groupid` IN (1, 2)) AND (`pages`.`perms_group` & 8 = 8)))'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getPagePermissionsClauseWithValidUserDataProvider
     * @param int $perms
     * @param bool $admin
     * @param string $groups
     * @param string $expected
     */
    public function getPagePermissionsClauseWithValidUser(int $perms, bool $admin, string $groups, string $expected)
    {
        // We only need to setup the mocking for the non-admin cases
        // If this setup is done for admin cases the FIFO behavior
        // of GeneralUtility::addInstance will influence other tests
        // as the ConnectionPool is never used!
        if (!$admin) {
            /** @var Connection|ObjectProphecy $connectionProphecy */
            $connectionProphecy = $this->prophesize(Connection::class);
            $connectionProphecy->getDatabasePlatform()->willReturn(new MockPlatform());
            $connectionProphecy->quoteIdentifier(Argument::cetera())->will(function ($args) {
                return '`' . str_replace('.', '`.`', $args[0]) . '`';
            });

            /** @var QueryBuilder|ObjectProphecy $queryBuilderProphecy */
            $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
            $queryBuilderProphecy->expr()->willReturn(
                new ExpressionBuilder($connectionProphecy->reveal())
            );

            /** @var ConnectionPool|ObjectProphecy $databaseProphecy */
            $databaseProphecy = $this->prophesize(ConnectionPool::class);
            $databaseProphecy->getQueryBuilderForTable('pages')->willReturn($queryBuilderProphecy->reveal());
            // Shift previously added instance
            GeneralUtility::makeInstance(ConnectionPool::class);
            GeneralUtility::addInstance(ConnectionPool::class, $databaseProphecy->reveal());
        }

        /** @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(BackendUserAuthentication::class)
            ->setMethods(['isAdmin'])
            ->getMock();
        $subject->expects($this->any())
            ->method('isAdmin')
            ->will($this->returnValue($admin));

        $subject->user = ['uid' => 123];
        $subject->groupList = $groups;

        $this->assertEquals($expected, $subject->getPagePermsClause($perms));
    }
}
