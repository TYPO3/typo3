<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Markus Günther <mail@markus-guenther>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Testcase for \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup.
 *
 * @author Markus Günther <mail@markus-guenther>
 * @api
 */
class BackendUserGroupTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup
	 */
	protected $fixture = NULL;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function getTitleInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getTitle());
	}

	/**
	 * @test
	 */
	public function setTitleSetsTitle() {
		$title = 'foo bar';
		$this->fixture->setTitle($title);
		$this->assertSame($title, $this->fixture->getTitle());
	}

	/**
	 * @test
	 */
	public function getDescriptionInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getDescription());
	}

	/**
	 * @test
	 */
	public function setDescriptionSetsDescription() {
		$description = 'foo bar';
		$this->fixture->setDescription($description);
		$this->assertSame($description, $this->fixture->getDescription());
	}

	/**
	 * @test
	 */
	public function setSubGroupsSetsSubgroups() {
		$subGroups = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->fixture->setSubGroups($subGroups);
		$this->assertSame($subGroups, $this->fixture->getSubGroups());
	}

	/**
	 * @test
	 */
	public function anSubGroupCanBeRemoved() {
		$group1 = new \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup();
		$group1->setTitle('foo');
		$group2 = new \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup();
		$group2->setTitle('bar');
		$this->fixture->addSubGroup($group1);
		$this->fixture->addSubGroup($group2);
		$this->assertEquals(count($this->fixture->getSubGroups()), 2);
		$this->fixture->removeSubGroup($group1);
		$this->assertEquals(count($this->fixture->getSubGroups()), 1);
		$this->fixture->removeSubGroup($group2);
		$this->assertEquals(count($this->fixture->getSubGroups()), 0);
	}

	/**
	 * @test
	 */
	public function allSubGroupsCanBeRemoved() {
		$group1 = new \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup();
		$group1->setTitle('foo');
		$group2 = new \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup();
		$group2->setTitle('bar');
		$this->fixture->addSubGroup($group1);
		$this->fixture->addSubGroup($group2);
		$this->fixture->removeAllSubGroups();
		$this->assertEquals(count($this->fixture->getSubGroups()), 0);
	}

	/**
	 * @test
	 */
	public function getModulesInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getModules());
	}

	/**
	 * @test
	 */
	public function setModulesSetsModules() {
		$modules = 'foo,bar';
		$this->fixture->setModules($modules);
		$this->assertSame($modules, $this->fixture->getModules());
	}

	/**
	 * @test
	 */
	public function getTablesListeningInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getTablesListening());
	}

	/**
	 * @test
	 */
	public function setTablesListeningSetsTablesListening() {
		$tablesListening = 'foo,bar';
		$this->fixture->setTablesListening($tablesListening);
		$this->assertSame($tablesListening, $this->fixture->getTablesListening());
	}

	/**
	 * @test
	 */
	public function getTablesModifyInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getTablesModify());
	}

	/**
	 * @test
	 */
	public function getPageTypesInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getPageTypes());
	}

	/**
	 * @test
	 */
	public function setPageTypesSetsPageTypes() {
		$pageTypes = 'foo,bar';
		$this->fixture->setPageTypes($pageTypes);
		$this->assertSame($pageTypes, $this->fixture->getPageTypes());
	}

	/**
	 * @test
	 */
	public function setTablesModifySetsTablesModify() {
		$tablesModify = 'foo,bar';
		$this->fixture->setTablesModify($tablesModify);
		$this->assertSame($tablesModify, $this->fixture->getTablesModify());
	}

	/**
	 * @test
	 */
	public function getAllowedExcludeFieldsInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getAllowedExcludeFields());
	}

	/**
	 * @test
	 */
	public function setAllowedExcludeFieldsSetsAllowedExcludeFields() {
		$allowedExcludeFields = 'foo,bar';
		$this->fixture->setAllowedExcludeFields($allowedExcludeFields);
		$this->assertSame($allowedExcludeFields, $this->fixture->getAllowedExcludeFields());
	}

	/**
	 * @test
	 */
	public function getExplicitlyAllowAndDenyInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getExplicitlyAllowAndDeny());
	}

	/**
	 * @test
	 */
	public function setExplicitlyAllowAndDenySetsExplicitlyAllowAndDeny() {
		$explicitlyAllowAndDeny = 'foo,bar';
		$this->fixture->setExplicitlyAllowAndDeny($explicitlyAllowAndDeny);
		$this->assertSame($explicitlyAllowAndDeny, $this->fixture->getExplicitlyAllowAndDeny());
	}

	/**
	 * @test
	 */
	public function getAllowedLanguagesInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getAllowedLanguages());
	}

	/**
	 * @test
	 */
	public function setAllowedLanguagesSetsAllowedLanguages() {
		$allowedLanguages = '1,0';
		$this->fixture->setAllowedLanguages($allowedLanguages);
		$this->assertSame($allowedLanguages, $this->fixture->getAllowedLanguages());
	}

	/**
	 * @test
	 */
	public function getWorkspacePermissionInitiallyReturnsFalse() {
		$this->assertFalse($this->fixture->getWorkspacePermission());
	}

	/**
	 * @test
	 */
	public function setWorkspacePermissionSetsWorkspacePermission() {
		$this->fixture->setWorkspacePermissions(TRUE);
		$this->assertTrue($this->fixture->getWorkspacePermission());
	}

	/**
	 * @test
	 */
	public function getDatabaseMountsInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getDatabaseMounts());
	}

	/**
	 * @test
	 */
	public function setDatabaseMountsSetsDatabaseMounts() {
		$mounts = '1,42';
		$this->fixture->setDatabaseMounts($mounts);
		$this->assertSame($mounts, $this->fixture->getDatabaseMounts());
	}

	/**
	 * @test
	 */
	public function getFileOperationPermissionsInitiallyReturnsZero() {
		$this->assertSame(0, $this->fixture->getFileOperationPermissions());
	}

	/**
	 * @test
	 */
	public function setFileOperationPermissionsSetsFileOperationPermissions() {
		$permission = 7;
		$this->fixture->setFileOperationPermissions($permission);
		$this->assertSame($permission, $this->fixture->getFileOperationPermissions());
	}

	/**
	 * @test
	 */
	public function getIsFileOperationAllowedReturnsFalse() {
		$this->fixture->setFileOperationPermissions(0);
		$this->assertFalse($this->fixture->isFileOperationAllowed());
		$this->fixture->setFileOperationPermissions(2);
		$this->assertFalse($this->fixture->isFileOperationAllowed());
		$this->fixture->setFileOperationPermissions(6);
		$this->assertFalse($this->fixture->isFileOperationAllowed());
	}

	/**
	 * @test
	 */
	public function getIsFileOperationAllowedReturnsTrue() {
		$this->fixture->setFileOperationPermissions(1);
		$this->assertTrue($this->fixture->isFileOperationAllowed());
		$this->fixture->setFileOperationPermissions(3);
		$this->assertTrue($this->fixture->isFileOperationAllowed());
	}

	/**
	 * @test
	 */
	public function setFileOperationAllowedSetsFileOperationAllowed() {
		$this->fixture->setFileOperationPermissions(0);
		$this->fixture->setFileOperationAllowed(TRUE);
		$this->assertTrue($this->fixture->isFileOperationAllowed());
	}

	/**
	 * @test
	 */
	public function getIsFileUnzipAllowedReturnsFalse() {
		$this->fixture->setFileOperationPermissions(0);
		$this->assertFalse($this->fixture->isFileUnzipAllowed());
		$this->fixture->setFileOperationPermissions(1);
		$this->assertFalse($this->fixture->isFileUnzipAllowed());
		$this->fixture->setFileOperationPermissions(5);
		$this->assertFalse($this->fixture->isFileUnzipAllowed());
	}

	/**
	 * @test
	 */
	public function getIsFileUnzipAllowedReturnsTrue() {
		$this->fixture->setFileOperationPermissions(2);
		$this->assertTrue($this->fixture->isFileUnzipAllowed());
		$this->fixture->setFileOperationPermissions(3);
		$this->assertTrue($this->fixture->isFileUnzipAllowed());
	}

	/**
	 * @test
	 */
	public function setFileUnzipAllowedSetsFileUnzipAllowed() {
		$this->fixture->setFileOperationPermissions(0);
		$this->fixture->setFileUnzipAllowed(TRUE);
		$this->assertTrue($this->fixture->isFileUnzipAllowed());
	}

	/**
	 * @test
	 */
	public function getIsDirectoryRemoveRecursivelyAllowedReturnsFalse() {
		$this->fixture->setFileOperationPermissions(1);
		$this->assertFalse($this->fixture->isDirectoryRemoveRecursivelyAllowed());
		$this->fixture->setFileOperationPermissions(15);
		$this->assertFalse($this->fixture->isDirectoryRemoveRecursivelyAllowed());
		$this->fixture->setFileOperationPermissions(7);
		$this->assertFalse($this->fixture->isDirectoryRemoveRecursivelyAllowed());
	}

	/**
	 * @test
	 */
	public function getIsDirectoryRemoveRecursivelyAllowedReturnsTrue() {
		$this->fixture->setFileOperationPermissions(16);
		$this->assertTrue($this->fixture->isDirectoryRemoveRecursivelyAllowed());
		$this->fixture->setFileOperationPermissions(31);
		$this->assertTrue($this->fixture->isDirectoryRemoveRecursivelyAllowed());
	}

	/**
	 * @test
	 */
	public function setDirectoryRemoveRecursivelyAllowedSetsDirectoryRemoveRecursivelyAllowed() {
		$this->fixture->setFileOperationPermissions(0);
		$this->fixture->setDirectoryRemoveRecursivelyAllowed(TRUE);
		$this->assertTrue($this->fixture->isDirectoryRemoveRecursivelyAllowed());
	}

	/**
	 * @test
	 */
	public function getIsDirectoryCopyAllowedReturnsFalse() {
		$this->fixture->setFileOperationPermissions(0);
		$this->assertFalse($this->fixture->isDirectoryCopyAllowed());
		$this->fixture->setFileOperationPermissions(7);
		$this->assertFalse($this->fixture->isDirectoryCopyAllowed());
		$this->fixture->setFileOperationPermissions(23);
		$this->assertFalse($this->fixture->isDirectoryCopyAllowed());
	}

	/**
	 * @test
	 */
	public function getIsDirectoryCopyAllowedReturnsTrue() {
		$this->fixture->setFileOperationPermissions(8);
		$this->assertTrue($this->fixture->isDirectoryCopyAllowed());
		$this->fixture->setFileOperationPermissions(15);
		$this->assertTrue($this->fixture->isDirectoryCopyAllowed());
	}

	/**
	 * @test
	 */
	public function setDirectoryCopyAllowedSetsDirectoryCopyAllowed() {
		$this->fixture->setFileOperationPermissions(0);
		$this->fixture->setDirectoryCopyAllowed(TRUE);
		$this->assertTrue($this->fixture->isDirectoryCopyAllowed());
	}

	/**
	 * @test
	 */
	public function getIsDirectoryOperationAllowedReturnsFalse() {
		$this->fixture->setFileOperationPermissions(0);
		$this->assertFalse($this->fixture->isDirectoryOperationAllowed());
		$this->fixture->setFileOperationPermissions(3);
		$this->assertFalse($this->fixture->isDirectoryOperationAllowed());
		$this->fixture->setFileOperationPermissions(11);
		$this->assertFalse($this->fixture->isDirectoryOperationAllowed());
	}

	/**
	 * @test
	 */
	public function getIsDirectoryOperationAllowedReturnsTrue() {
		$this->fixture->setFileOperationPermissions(4);
		$this->assertTrue($this->fixture->isDirectoryOperationAllowed());
		$this->fixture->setFileOperationPermissions(7);
		$this->assertTrue($this->fixture->isDirectoryOperationAllowed());
	}

	/**
	 * @test
	 */
	public function setDirectoryOperationAllowedSetsDirectoryOperationAllowed() {
		$this->fixture->setFileOperationPermissions(0);
		$this->fixture->setDirectoryoperationAllowed(TRUE);
		$this->assertTrue($this->fixture->isDirectoryOperationAllowed());
	}

	/**
	 * @test
	 */
	public function getLockToDomainInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getLockToDomain());
	}

	/**
	 * @test
	 */
	public function setLockToDomainSetsLockToDomain() {
		$lockToDomain = 'foo.bar';
		$this->fixture->setLockToDomain($lockToDomain);
		$this->assertSame($lockToDomain, $this->fixture->getLockToDomain());
	}

	/**
	 * @test
	 */
	public function getHideInListInitiallyReturnsFalse() {
		$this->assertFalse($this->fixture->getHideInList());
	}

	/**
	 * @test
	 */
	public function setHideInListSetsHideInList() {
		$this->fixture->setHideInList(TRUE);
		$this->assertTrue($this->fixture->getHideInList());
	}

	/**
	 * @test
	 */
	public function getTsConfigInitiallyReturnsEmptyString() {
		$this->assertSame('', $this->fixture->getTsConfig());
	}

	/**
	 * @test
	 */
	public function setTsConfigSetsTsConfig() {
		$tsConfig = 'foo bar';
		$this->fixture->setTsConfig($tsConfig);
		$this->assertSame($tsConfig, $this->fixture->getTsConfig());
	}
}

?>