<?php
namespace TYPO3\CMS\Core\Tests\Unit\Authentication;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2011 Oliver Klee (typo3-coding@oliverklee.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class BackendUserAuthenticationTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	private $fixture = NULL;

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	public function setUp() {
		// reset hooks
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'] = array();
		$this->fixture = new \TYPO3\CMS\Core\Authentication\BackendUserAuthentication();
	}

	public function tearDown() {
		unset($this->fixture);
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::purgeInstances();
	}

	/////////////////////////////////////////
	// Tests concerning the form protection
	/////////////////////////////////////////
	/**
	 * @test
	 */
	public function logoffCleansFormProtection() {
		$formProtection = $this->getMock('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection', array('clean'));
		$formProtection->expects($this->atLeastOnce())->method('clean');
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::set('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection', $formProtection);
		$this->fixture->logoff();
	}

	/**
	 * @return array
	 */
	public function getTSConfigDataProvider() {

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
					'value' => NULL,
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
					'value' => NULL,
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
					'value' => NULL,
					'properties' =>
					array('readAction' => '1'),
				),
			),
			'three levels string with integer property' => array(
				$completeConfiguration,
				'permissions.file.1',
				array(
					'value' => NULL,
					'properties' => array('writeAction' => '1'),
				),
			),
			'three levels string with integer zero property' => array(
				$completeConfiguration,
				'permissions.file.0',
				array(
					'value' => NULL,
					'properties' => array('readAction' => '0'),
				),
			),
			'four levels string with integer zero property, value, no properties' => array(
				$completeConfiguration,
				'permissions.file.0.readAction',
				array(
					'value' => '0',
					'properties' => NULL,
				),
			),
			'four levels string with integer property, value, no properties' => array(
				$completeConfiguration,
				'permissions.file.1.writeAction',
				array(
					'value' => '1',
					'properties' => NULL,
				),
			),
			'one level, not existant string' => array(
				$completeConfiguration,
				'foo',
				array(
					'value' => NULL,
					'properties' => NULL,
				),
			),
			'two level, not existant string' => array(
				$completeConfiguration,
				'foo.bar',
				array(
					'value' => NULL,
					'properties' => NULL,
				),
			),
			'two level, where second level does not exist' => array(
				$completeConfiguration,
				'permissions.bar',
				array(
					'value' => NULL,
					'properties' => NULL,
				),
			),
			'three level, where third level does not exist' => array(
				$completeConfiguration,
				'permissions.file.foo',
				array(
					'value' => NULL,
					'properties' => NULL,
				),
			),
			'three level, where second and third level does not exist' => array(
				$completeConfiguration,
				'permissions.foo.bar',
				array(
					'value' => NULL,
					'properties' => NULL,
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
	public function getTSConfigReturnsCorrectArrayForGivenObjectString(array $completeConfiguration, $objectString, array $expectedConfiguration) {
		$this->fixture->userTS = $completeConfiguration;

		$actualConfiguration = $this->fixture->getTSConfig($objectString);
		$this->assertSame($expectedConfiguration, $actualConfiguration);
	}

	/**
	 * @return array
	 */
	public function getFilePermissionsTakesUserDefaultAndStoragePermissionsIntoAccountIfUserIsNotAdminDataProvider() {
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
	 * @param array $expectedPermissions
	 * @test
	 * @dataProvider getFilePermissionsTakesUserDefaultAndStoragePermissionsIntoAccountIfUserIsNotAdminDataProvider
	 */
	public function getFilePermissionsTakesUserDefaultPermissionsFromTsConfigIntoAccountIfUserIsNotAdmin(array $expectedPermissions) {
		$this->fixture = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication', array('isAdmin', 'getFileoperationPermissions'));

		$this->fixture
			->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue(FALSE));

		$this->fixture
			->expects($this->never())
			->method('getFileoperationPermissions');

		$this->fixture->userTS = array(
			'permissions.' => array(
				'file.' => array(
					'default.' => $expectedPermissions
				),
			)
		);

		$this->assertEquals($expectedPermissions, $this->fixture->getFilePermissions());
	}

	/**
	 * @return array
	 */
	public function getFilePermissionsFromStorageDataProvider() {
		$defaultPermissions = array(
			'addFile' => 1,
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
			'recursivedeleteFolder' => 1
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
			'Returns default permissions if no storage permissions are found' => array(
				$defaultPermissions,
				1,
				array(),
				array(
					'addFile' => 1,
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
					'recursivedeleteFolder' => 1
				)
			),
		);
	}

	/**
	 * @param array $defaultPermissions
	 * @param integer $storageUid
	 * @param array $storagePermissions
	 * @param array $expectedPermissions
	 * @test
	 * @dataProvider getFilePermissionsFromStorageDataProvider
	 */
	public function getFilePermissionsFromStorageOverwritesDefaultPermissions(array $defaultPermissions, $storageUid, array $storagePermissions, array $expectedPermissions) {
		$this->fixture = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication', array('isAdmin', 'getFilePermissions'));
		$storageMock = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);
		$storageMock->expects($this->any())->method('getUid')->will($this->returnValue($storageUid));

		$this->fixture
			->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue(FALSE));

		$this->fixture
			->expects($this->any())
			->method('getFilePermissions')
			->will($this->returnValue($defaultPermissions));

		$this->fixture->userTS = array(
			'permissions.' => array(
				'file.' => array(
					'storage.' => array(
						$storageUid . '.' => $storagePermissions
					),
				),
			)
		);

		$this->assertEquals($expectedPermissions, $this->fixture->getFilePermissionsForStorage($storageMock));
	}

	/**
	 * @param array $defaultPermissions
	 * @param $storageUid
	 * @param array $storagePermissions
	 * @test
	 * @dataProvider getFilePermissionsFromStorageDataProvider
	 */
	public function getFilePermissionsFromStorageAlwaysReturnsDefaultPermissionsForAdmins(array $defaultPermissions, $storageUid, array $storagePermissions) {
		$this->fixture = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication', array('isAdmin', 'getFilePermissions'));
		$storageMock = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', array(), array(), '', FALSE);
		$storageMock->expects($this->any())->method('getUid')->will($this->returnValue($storageUid));

		$this->fixture
			->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue(TRUE));

		$this->fixture
			->expects($this->any())
			->method('getFilePermissions')
			->will($this->returnValue($defaultPermissions));

		$this->fixture->userTS = array(
			'permissions.' => array(
				'file.' => array(
					'storage.' => array(
						$storageUid . '.' => $storagePermissions
					),
				),
			)
		);

		$this->assertEquals($defaultPermissions, $this->fixture->getFilePermissionsForStorage($storageMock));
	}

	/**
	 * @return array
	 */
	public function getFilePermissionsTakesUserDefaultPermissionsFromRecordIntoAccountIfUserIsNotAdminDataProvider() {
		return array(
			'No old permission' => array(
				0,
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
					'recursivedeleteFolder' => 0
				)
			),
			'Uploading allowed' => array(
				1,
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
			'Unzip allowed' => array(
				2,
				array(
					'addFile' => 0,
					'readFile' => 1,
					'writeFile' => 0,
					'copyFile' => 0,
					'moveFile' => 0,
					'renameFile' => 0,
					'unzipFile' => 1,
					'deleteFile' => 0,
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
			'Write folder allowed' => array(
				4,
				array(
					'addFile' => 0,
					'readFile' => 1,
					'writeFile' => 0,
					'copyFile' => 0,
					'moveFile' => 0,
					'renameFile' => 0,
					'unzipFile' => 0,
					'deleteFile' => 0,
					'addFolder' => 1,
					'readFolder' => 1,
					'copyFolder' => 0,
					'moveFolder' => 1,
					'renameFolder' => 1,
					'writeFolder' => 1,
					'deleteFolder' => 1,
					'recursivedeleteFolder' => 0
				)
			),
			'Copy folder allowed' => array(
				8,
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
					'copyFolder' => 1,
					'moveFolder' => 0,
					'renameFolder' => 0,
					'writeFolder' => 0,
					'deleteFolder' => 0,
					'recursivedeleteFolder' => 0
				)
			),
			'Copy folder and remove subfolders allowed' => array(
				24,
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
					'copyFolder' => 1,
					'moveFolder' => 0,
					'renameFolder' => 0,
					'writeFolder' => 0,
					'deleteFolder' => 0,
					'recursivedeleteFolder' => 1
				)
			),
		);
	}

	/**
	 * @test
	 * @dataProvider getFilePermissionsTakesUserDefaultPermissionsFromRecordIntoAccountIfUserIsNotAdminDataProvider
	 */
	public function getFilePermissionsTakesUserDefaultPermissionsFromRecordIntoAccountIfUserIsNotAdmin($oldPermissionValue, $expectedPermissions) {
		$this->fixture = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication', array('isAdmin', 'getFileoperationPermissions'));

		$this->fixture
			->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue(FALSE));

		$this->fixture
			->expects($this->any())
			->method('getFileoperationPermissions')
			->will($this->returnValue($oldPermissionValue));

		$this->fixture->userTS = array();
		$this->assertEquals($expectedPermissions, $this->fixture->getFilePermissions());
	}

	/**
	 * @test
	 */
	public function getFilePermissionsGrantsAllPermissionsToAdminUsers() {
		$this->fixture = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication', array('isAdmin', 'getFileoperationPermissions'));

		$this->fixture
			->expects($this->any())
			->method('isAdmin')
			->will($this->returnValue(TRUE));

		$expectedPermissions = array(
			'addFile' => TRUE,
			'readFile' => TRUE,
			'writeFile' => TRUE,
			'copyFile' => TRUE,
			'moveFile' => TRUE,
			'renameFile' => TRUE,
			'unzipFile' => TRUE,
			'deleteFile' => TRUE,
			'addFolder' => TRUE,
			'readFolder' => TRUE,
			'copyFolder' => TRUE,
			'moveFolder' => TRUE,
			'renameFolder' => TRUE,
			'writeFolder' => TRUE,
			'deleteFolder' => TRUE,
			'recursivedeleteFolder' => TRUE
		);

		$this->assertEquals($expectedPermissions, $this->fixture->getFilePermissions());
	}

}

?>
