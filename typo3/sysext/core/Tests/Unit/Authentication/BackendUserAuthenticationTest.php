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

/**
 * Testcase for \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
 */
class BackendUserAuthenticationTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var array
     */
    protected $defaultFilePermissions = array(
        // File permissions
        'addFile' => false,
        'readFile' => false,
        'writeFile' => false,
        'copyFile' => false,
        'moveFile' => false,
        'renameFile' => false,
        'unzipFile' => false,
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
    );

    protected function setUp()
    {
        // reset hooks
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'] = array();
    }

    protected function tearDown()
    {
        \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::purgeInstances();
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
        $formProtection = $this->getMock(
            \TYPO3\CMS\Core\FormProtection\BackendFormProtection::class,
            array('clean'),
            array(),
            '',
            false
        );
        $formProtection->expects($this->once())->method('clean');

        \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::set(
            'default',
            $formProtection
        );

        // logoff() call the static factory that has a dependency to a valid BE_USER object. Mock this away
        $GLOBALS['BE_USER'] = $this->getMock(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class, array(), array(), '', false);
        $GLOBALS['BE_USER']->user = array('uid' => $this->getUniqueId());
        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, array(), array(), '', false);

        $subject = $this->getAccessibleMock(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class, array('dummy'), array(), '', false);
        $subject->_set('db', $GLOBALS['TYPO3_DB']);
        $subject->logoff();
    }

    /**
     * @return array
     */
    public function getTSConfigDataProvider()
    {
        $completeConfiguration = array(
            'value' => 'oneValue',
            'value.' => array('oneProperty' => 'oneValue'),
            'permissions.' => array(
                'file.' => array(
                    'default.' => array('readAction' => '1'),
                    '1.' => array('writeAction' => '1'),
                    '0.' => array('readAction' => '0'),
                ),
            )
        );

        return array(
            'single level string' => array(
                $completeConfiguration,
                'permissions',
                array(
                    'value' => null,
                    'properties' =>
                    array(
                        'file.' => array(
                            'default.' => array('readAction' => '1'),
                            '1.' => array('writeAction' => '1'),
                            '0.' => array('readAction' => '0'),
                        ),
                    ),
                ),
            ),
            'two levels string' => array(
                $completeConfiguration,
                'permissions.file',
                array(
                    'value' => null,
                    'properties' =>
                    array(
                        'default.' => array('readAction' => '1'),
                        '1.' => array('writeAction' => '1'),
                        '0.' => array('readAction' => '0'),
                    ),
                ),
            ),
            'three levels string' => array(
                $completeConfiguration,
                'permissions.file.default',
                array(
                    'value' => null,
                    'properties' =>
                    array('readAction' => '1'),
                ),
            ),
            'three levels string with integer property' => array(
                $completeConfiguration,
                'permissions.file.1',
                array(
                    'value' => null,
                    'properties' => array('writeAction' => '1'),
                ),
            ),
            'three levels string with integer zero property' => array(
                $completeConfiguration,
                'permissions.file.0',
                array(
                    'value' => null,
                    'properties' => array('readAction' => '0'),
                ),
            ),
            'four levels string with integer zero property, value, no properties' => array(
                $completeConfiguration,
                'permissions.file.0.readAction',
                array(
                    'value' => '0',
                    'properties' => null,
                ),
            ),
            'four levels string with integer property, value, no properties' => array(
                $completeConfiguration,
                'permissions.file.1.writeAction',
                array(
                    'value' => '1',
                    'properties' => null,
                ),
            ),
            'one level, not existent string' => array(
                $completeConfiguration,
                'foo',
                array(
                    'value' => null,
                    'properties' => null,
                ),
            ),
            'two level, not existent string' => array(
                $completeConfiguration,
                'foo.bar',
                array(
                    'value' => null,
                    'properties' => null,
                ),
            ),
            'two level, where second level does not exist' => array(
                $completeConfiguration,
                'permissions.bar',
                array(
                    'value' => null,
                    'properties' => null,
                ),
            ),
            'three level, where third level does not exist' => array(
                $completeConfiguration,
                'permissions.file.foo',
                array(
                    'value' => null,
                    'properties' => null,
                ),
            ),
            'three level, where second and third level does not exist' => array(
                $completeConfiguration,
                'permissions.foo.bar',
                array(
                    'value' => null,
                    'properties' => null,
                ),
            ),
            'value and properties' => array(
                $completeConfiguration,
                'value',
                array(
                    'value' => 'oneValue',
                    'properties' => array('oneProperty' => 'oneValue'),
                ),
            ),
        );
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
        $subject = $this->getMock(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class, array('dummy'), array(), '', false);
        $subject->userTS = $completeConfiguration;

        $actualConfiguration = $subject->getTSConfig($objectString);
        $this->assertSame($expectedConfiguration, $actualConfiguration);
    }

    /**
     * @return array
     */
    public function getFilePermissionsTakesUserDefaultAndStoragePermissionsIntoAccountIfUserIsNotAdminDataProvider()
    {
        return array(
            'Only read permissions' => array(
                array(
                    'addFile' => 0,
                    'readFile' => 1,
                    'writeFile' => 0,
                    'copyFile' => 0,
                    'moveFile' => 0,
                    'renameFile' => 0,
                    'unzipFile' => 0,
                    'deleteFile' => 0,
                    'addFolder' => 0,
                    'readFolder' => 1,
                    'copyFolder' => 0,
                    'moveFolder' => 0,
                    'renameFolder' => 0,
                    'writeFolder' => 0,
                    'deleteFolder' => 0,
                    'recursivedeleteFolder' => 0,
                )
            ),
            'Uploading allowed' => array(
                array(
                    'addFile' => 1,
                    'readFile' => 1,
                    'writeFile' => 1,
                    'copyFile' => 1,
                    'moveFile' => 1,
                    'renameFile' => 1,
                    'unzipFile' => 0,
                    'deleteFile' => 1,
                    'addFolder' => 0,
                    'readFolder' => 1,
                    'copyFolder' => 0,
                    'moveFolder' => 0,
                    'renameFolder' => 0,
                    'writeFolder' => 0,
                    'deleteFolder' => 0,
                    'recursivedeleteFolder' => 0
                )
            ),
            'One value is enough' => array(
                array(
                    'addFile' => 1,
                )
            ),
        );
    }

    /**
     * @param array $userTsConfiguration
     * @test
     * @dataProvider getFilePermissionsTakesUserDefaultAndStoragePermissionsIntoAccountIfUserIsNotAdminDataProvider
     */
    public function getFilePermissionsTakesUserDefaultPermissionsFromTsConfigIntoAccountIfUserIsNotAdmin(array $userTsConfiguration)
    {
        $subject = $this->getMock(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class, array('isAdmin'));

        $subject
            ->expects($this->any())
            ->method('isAdmin')
            ->will($this->returnValue(false));

        $subject->userTS = array(
            'permissions.' => array(
                'file.' => array(
                    'default.' => $userTsConfiguration
                ),
            )
        );

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
        $defaultPermissions = array(
            'addFile' => true,
            'readFile' => true,
            'writeFile' => true,
            'copyFile' => true,
            'moveFile' => true,
            'renameFile' => true,
            'unzipFile' => true,
            'deleteFile' => true,
            'addFolder' => true,
            'readFolder' => true,
            'copyFolder' => true,
            'moveFolder' => true,
            'renameFolder' => true,
            'writeFolder' => true,
            'deleteFolder' => true,
            'recursivedeleteFolder' => true
        );

        return array(
            'Overwrites given storage permissions with default permissions' => array(
                $defaultPermissions,
                1,
                array(
                    'addFile' => 0,
                    'recursivedeleteFolder' =>0
                ),
                array(
                    'addFile' => 0,
                    'readFile' => 1,
                    'writeFile' => 1,
                    'copyFile' => 1,
                    'moveFile' => 1,
                    'renameFile' => 1,
                    'unzipFile' => 1,
                    'deleteFile' => 1,
                    'addFolder' => 1,
                    'readFolder' => 1,
                    'copyFolder' => 1,
                    'moveFolder' => 1,
                    'renameFolder' => 1,
                    'writeFolder' => 1,
                    'deleteFolder' => 1,
                    'recursivedeleteFolder' => 0
                )
            ),
            'Overwrites given storage 0 permissions with default permissions' => array(
                $defaultPermissions,
                0,
                array(
                    'addFile' => 0,
                    'recursivedeleteFolder' =>0
                ),
                array(
                    'addFile' => false,
                    'readFile' => true,
                    'writeFile' => true,
                    'copyFile' => true,
                    'moveFile' => true,
                    'renameFile' => true,
                    'unzipFile' => true,
                    'deleteFile' => true,
                    'addFolder' => true,
                    'readFolder' => true,
                    'copyFolder' => true,
                    'moveFolder' => true,
                    'renameFolder' => true,
                    'writeFolder' => true,
                    'deleteFolder' => true,
                    'recursivedeleteFolder' => false
                )
            ),
            'Returns default permissions if no storage permissions are found' => array(
                $defaultPermissions,
                1,
                array(),
                array(
                    'addFile' => true,
                    'readFile' => true,
                    'writeFile' => true,
                    'copyFile' => true,
                    'moveFile' => true,
                    'renameFile' => true,
                    'unzipFile' => true,
                    'deleteFile' => true,
                    'addFolder' => true,
                    'readFolder' => true,
                    'copyFolder' => true,
                    'moveFolder' => true,
                    'renameFolder' => true,
                    'writeFolder' => true,
                    'deleteFolder' => true,
                    'recursivedeleteFolder' => true
                )
            ),
        );
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
        $subject = $this->getMock(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class, array('isAdmin', 'getFilePermissions'));
        $storageMock = $this->getMock(\TYPO3\CMS\Core\Resource\ResourceStorage::class, array(), array(), '', false);
        $storageMock->expects($this->any())->method('getUid')->will($this->returnValue($storageUid));

        $subject
            ->expects($this->any())
            ->method('isAdmin')
            ->will($this->returnValue(false));

        $subject
            ->expects($this->any())
            ->method('getFilePermissions')
            ->will($this->returnValue($defaultPermissions));

        $subject->userTS = array(
            'permissions.' => array(
                'file.' => array(
                    'storage.' => array(
                        $storageUid . '.' => $storagePermissions
                    ),
                ),
            )
        );

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
        $subject = $this->getMock(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class, array('isAdmin', 'getFilePermissions'));
        $storageMock = $this->getMock(\TYPO3\CMS\Core\Resource\ResourceStorage::class, array(), array(), '', false);
        $storageMock->expects($this->any())->method('getUid')->will($this->returnValue($storageUid));

        $subject
            ->expects($this->any())
            ->method('isAdmin')
            ->will($this->returnValue(true));

        $subject
            ->expects($this->any())
            ->method('getFilePermissions')
            ->will($this->returnValue($defaultPermissions));

        $subject->userTS = array(
            'permissions.' => array(
                'file.' => array(
                    'storage.' => array(
                        $storageUid . '.' => $storagePermissions
                    ),
                ),
            )
        );

        $this->assertEquals($defaultPermissions, $subject->getFilePermissionsForStorage($storageMock));
    }

    /**
     * @return array
     */
    public function getFilePermissionsTakesUserDefaultPermissionsFromRecordIntoAccountIfUserIsNotAdminDataProvider()
    {
        return array(
            'No permission' => array(
                '',
                array(
                    'addFile' => false,
                    'readFile' => false,
                    'writeFile' => false,
                    'copyFile' => false,
                    'moveFile' => false,
                    'renameFile' => false,
                    'unzipFile' => false,
                    'deleteFile' => false,
                    'addFolder' => false,
                    'readFolder' => false,
                    'copyFolder' => false,
                    'moveFolder' => false,
                    'renameFolder' => false,
                    'writeFolder' => false,
                    'deleteFolder' => false,
                    'recursivedeleteFolder' => false
                )
            ),
            'Standard file permissions' => array(
                'addFile,readFile,writeFile,copyFile,moveFile,renameFile,deleteFile',
                array(
                    'addFile' => true,
                    'readFile' => true,
                    'writeFile' => true,
                    'copyFile' => true,
                    'moveFile' => true,
                    'renameFile' => true,
                    'unzipFile' => false,
                    'deleteFile' => true,
                    'addFolder' => false,
                    'readFolder' => false,
                    'copyFolder' => false,
                    'moveFolder' => false,
                    'renameFolder' => false,
                    'writeFolder' => false,
                    'deleteFolder' => false,
                    'recursivedeleteFolder' => false
                )
            ),
            'Unzip allowed' => array(
                'readFile,unzipFile',
                array(
                    'addFile' => false,
                    'readFile' => true,
                    'writeFile' => false,
                    'copyFile' => false,
                    'moveFile' => false,
                    'renameFile' => false,
                    'unzipFile' => true,
                    'deleteFile' => false,
                    'addFolder' => false,
                    'readFolder' => false,
                    'writeFolder' => false,
                    'copyFolder' => false,
                    'moveFolder' => false,
                    'renameFolder' => false,
                    'deleteFolder' => false,
                    'recursivedeleteFolder' => false
                )
            ),
            'Standard folder permissions' => array(
                'addFolder,readFolder,moveFolder,renameFolder,writeFolder,deleteFolder',
                array(
                    'addFile' => false,
                    'readFile' => false,
                    'writeFile' => false,
                    'copyFile' => false,
                    'moveFile' => false,
                    'renameFile' => false,
                    'unzipFile' => false,
                    'deleteFile' => false,
                    'addFolder' => true,
                    'readFolder' => true,
                    'writeFolder' => true,
                    'copyFolder' => false,
                    'moveFolder' => true,
                    'renameFolder' => true,
                    'deleteFolder' => true,
                    'recursivedeleteFolder' => false
                )
            ),
            'Copy folder allowed' => array(
                'readFolder,copyFolder',
                array(
                    'addFile' => false,
                    'readFile' => false,
                    'writeFile' => false,
                    'copyFile' => false,
                    'moveFile' => false,
                    'renameFile' => false,
                    'unzipFile' => false,
                    'deleteFile' => false,
                    'addFolder' => false,
                    'readFolder' => true,
                    'writeFolder' => false,
                    'copyFolder' => true,
                    'moveFolder' => false,
                    'renameFolder' => false,
                    'deleteFolder' => false,
                    'recursivedeleteFolder' => false
                )
            ),
            'Copy folder and remove subfolders allowed' => array(
                'readFolder,copyFolder,recursivedeleteFolder',
                array(
                    'addFile' => false,
                    'readFile' => false,
                    'writeFile' => false,
                    'copyFile' => false,
                    'moveFile' => false,
                    'renameFile' => false,
                    'unzipFile' => false,
                    'deleteFile' => false,
                    'addFolder' => false,
                    'readFolder' => true,
                    'writeFolder' => false,
                    'copyFolder' => true,
                    'moveFolder' => false,
                    'renameFolder' => false,
                    'deleteFolder' => false,
                    'recursivedeleteFolder' => true
                )
            ),
        );
    }

    /**
     * @test
     * @dataProvider getFilePermissionsTakesUserDefaultPermissionsFromRecordIntoAccountIfUserIsNotAdminDataProvider
     */
    public function getFilePermissionsTakesUserDefaultPermissionsFromRecordIntoAccountIfUserIsNotAdmin($permissionValue, $expectedPermissions)
    {
        $subject = $this->getMock(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class, array('isAdmin'));

        $subject
            ->expects($this->any())
            ->method('isAdmin')
            ->will($this->returnValue(false));

        $subject->userTS = array();
        $subject->groupData['file_permissions'] = $permissionValue;
        $this->assertEquals($expectedPermissions, $subject->getFilePermissions());
    }

    /**
     * @test
     */
    public function getFilePermissionsGrantsAllPermissionsToAdminUsers()
    {
        $subject = $this->getMock(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class, array('isAdmin'));

        $subject
            ->expects($this->any())
            ->method('isAdmin')
            ->will($this->returnValue(true));

        $expectedPermissions = array(
            'addFile' => true,
            'readFile' => true,
            'writeFile' => true,
            'copyFile' => true,
            'moveFile' => true,
            'renameFile' => true,
            'unzipFile' => true,
            'deleteFile' => true,
            'addFolder' => true,
            'readFolder' => true,
            'writeFolder' => true,
            'copyFolder' => true,
            'moveFolder' => true,
            'renameFolder' => true,
            'deleteFolder' => true,
            'recursivedeleteFolder' => true
        );

        $this->assertEquals($expectedPermissions, $subject->getFilePermissions());
    }
}
