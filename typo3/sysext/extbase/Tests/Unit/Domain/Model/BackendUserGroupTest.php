<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Domain\Model;

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
 * Test case
 */
class BackendUserGroupTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup();
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $title = 'foo bar';
        $this->subject->setTitle($title);
        $this->assertSame($title, $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function getDescriptionInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getDescription());
    }

    /**
     * @test
     */
    public function setDescriptionSetsDescription()
    {
        $description = 'foo bar';
        $this->subject->setDescription($description);
        $this->assertSame($description, $this->subject->getDescription());
    }

    /**
     * @test
     */
    public function setSubGroupsSetsSubgroups()
    {
        $subGroups = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->subject->setSubGroups($subGroups);
        $this->assertSame($subGroups, $this->subject->getSubGroups());
    }

    /**
     * @test
     */
    public function anSubGroupCanBeRemoved()
    {
        $group1 = new \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup();
        $group1->setTitle('foo');
        $group2 = new \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup();
        $group2->setTitle('bar');
        $this->subject->addSubGroup($group1);
        $this->subject->addSubGroup($group2);
        $this->assertEquals(count($this->subject->getSubGroups()), 2);
        $this->subject->removeSubGroup($group1);
        $this->assertEquals(count($this->subject->getSubGroups()), 1);
        $this->subject->removeSubGroup($group2);
        $this->assertEquals(count($this->subject->getSubGroups()), 0);
    }

    /**
     * @test
     */
    public function allSubGroupsCanBeRemoved()
    {
        $group1 = new \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup();
        $group1->setTitle('foo');
        $group2 = new \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup();
        $group2->setTitle('bar');
        $this->subject->addSubGroup($group1);
        $this->subject->addSubGroup($group2);
        $this->subject->removeAllSubGroups();
        $this->assertEquals(count($this->subject->getSubGroups()), 0);
    }

    /**
     * @test
     */
    public function getModulesInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getModules());
    }

    /**
     * @test
     */
    public function setModulesSetsModules()
    {
        $modules = 'foo,bar';
        $this->subject->setModules($modules);
        $this->assertSame($modules, $this->subject->getModules());
    }

    /**
     * @test
     */
    public function getTablesListeningInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getTablesListening());
    }

    /**
     * @test
     */
    public function setTablesListeningSetsTablesListening()
    {
        $tablesListening = 'foo,bar';
        $this->subject->setTablesListening($tablesListening);
        $this->assertSame($tablesListening, $this->subject->getTablesListening());
    }

    /**
     * @test
     */
    public function getTablesModifyInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getTablesModify());
    }

    /**
     * @test
     */
    public function getPageTypesInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getPageTypes());
    }

    /**
     * @test
     */
    public function setPageTypesSetsPageTypes()
    {
        $pageTypes = 'foo,bar';
        $this->subject->setPageTypes($pageTypes);
        $this->assertSame($pageTypes, $this->subject->getPageTypes());
    }

    /**
     * @test
     */
    public function setTablesModifySetsTablesModify()
    {
        $tablesModify = 'foo,bar';
        $this->subject->setTablesModify($tablesModify);
        $this->assertSame($tablesModify, $this->subject->getTablesModify());
    }

    /**
     * @test
     */
    public function getAllowedExcludeFieldsInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getAllowedExcludeFields());
    }

    /**
     * @test
     */
    public function setAllowedExcludeFieldsSetsAllowedExcludeFields()
    {
        $allowedExcludeFields = 'foo,bar';
        $this->subject->setAllowedExcludeFields($allowedExcludeFields);
        $this->assertSame($allowedExcludeFields, $this->subject->getAllowedExcludeFields());
    }

    /**
     * @test
     */
    public function getExplicitlyAllowAndDenyInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getExplicitlyAllowAndDeny());
    }

    /**
     * @test
     */
    public function setExplicitlyAllowAndDenySetsExplicitlyAllowAndDeny()
    {
        $explicitlyAllowAndDeny = 'foo,bar';
        $this->subject->setExplicitlyAllowAndDeny($explicitlyAllowAndDeny);
        $this->assertSame($explicitlyAllowAndDeny, $this->subject->getExplicitlyAllowAndDeny());
    }

    /**
     * @test
     */
    public function getAllowedLanguagesInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getAllowedLanguages());
    }

    /**
     * @test
     */
    public function setAllowedLanguagesSetsAllowedLanguages()
    {
        $allowedLanguages = '1,0';
        $this->subject->setAllowedLanguages($allowedLanguages);
        $this->assertSame($allowedLanguages, $this->subject->getAllowedLanguages());
    }

    /**
     * @test
     */
    public function getWorkspacePermissionInitiallyReturnsFalse()
    {
        $this->assertFalse($this->subject->getWorkspacePermission());
    }

    /**
     * @test
     */
    public function setWorkspacePermissionSetsWorkspacePermission()
    {
        $this->subject->setWorkspacePermissions(true);
        $this->assertTrue($this->subject->getWorkspacePermission());
    }

    /**
     * @test
     */
    public function getDatabaseMountsInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getDatabaseMounts());
    }

    /**
     * @test
     */
    public function setDatabaseMountsSetsDatabaseMounts()
    {
        $mounts = '1,42';
        $this->subject->setDatabaseMounts($mounts);
        $this->assertSame($mounts, $this->subject->getDatabaseMounts());
    }

    /**
     * @test
     */
    public function getFileOperationPermissionsInitiallyReturnsZero()
    {
        $this->assertSame(0, $this->subject->getFileOperationPermissions());
    }

    /**
     * @test
     */
    public function setFileOperationPermissionsSetsFileOperationPermissions()
    {
        $permission = 7;
        $this->subject->setFileOperationPermissions($permission);
        $this->assertSame($permission, $this->subject->getFileOperationPermissions());
    }

    /**
     * @test
     */
    public function getIsFileOperationAllowedReturnsFalse()
    {
        $this->subject->setFileOperationPermissions(0);
        $this->assertFalse($this->subject->isFileOperationAllowed());
        $this->subject->setFileOperationPermissions(2);
        $this->assertFalse($this->subject->isFileOperationAllowed());
        $this->subject->setFileOperationPermissions(6);
        $this->assertFalse($this->subject->isFileOperationAllowed());
    }

    /**
     * @test
     */
    public function getIsFileOperationAllowedReturnsTrue()
    {
        $this->subject->setFileOperationPermissions(1);
        $this->assertTrue($this->subject->isFileOperationAllowed());
        $this->subject->setFileOperationPermissions(3);
        $this->assertTrue($this->subject->isFileOperationAllowed());
    }

    /**
     * @test
     */
    public function setFileOperationAllowedSetsFileOperationAllowed()
    {
        $this->subject->setFileOperationPermissions(0);
        $this->subject->setFileOperationAllowed(true);
        $this->assertTrue($this->subject->isFileOperationAllowed());
    }

    /**
     * @test
     */
    public function getIsFileUnzipAllowedReturnsFalse()
    {
        $this->subject->setFileOperationPermissions(0);
        $this->assertFalse($this->subject->isFileUnzipAllowed());
        $this->subject->setFileOperationPermissions(1);
        $this->assertFalse($this->subject->isFileUnzipAllowed());
        $this->subject->setFileOperationPermissions(5);
        $this->assertFalse($this->subject->isFileUnzipAllowed());
    }

    /**
     * @test
     */
    public function getIsFileUnzipAllowedReturnsTrue()
    {
        $this->subject->setFileOperationPermissions(2);
        $this->assertTrue($this->subject->isFileUnzipAllowed());
        $this->subject->setFileOperationPermissions(3);
        $this->assertTrue($this->subject->isFileUnzipAllowed());
    }

    /**
     * @test
     */
    public function setFileUnzipAllowedSetsFileUnzipAllowed()
    {
        $this->subject->setFileOperationPermissions(0);
        $this->subject->setFileUnzipAllowed(true);
        $this->assertTrue($this->subject->isFileUnzipAllowed());
    }

    /**
     * @test
     */
    public function getIsDirectoryRemoveRecursivelyAllowedReturnsFalse()
    {
        $this->subject->setFileOperationPermissions(1);
        $this->assertFalse($this->subject->isDirectoryRemoveRecursivelyAllowed());
        $this->subject->setFileOperationPermissions(15);
        $this->assertFalse($this->subject->isDirectoryRemoveRecursivelyAllowed());
        $this->subject->setFileOperationPermissions(7);
        $this->assertFalse($this->subject->isDirectoryRemoveRecursivelyAllowed());
    }

    /**
     * @test
     */
    public function getIsDirectoryRemoveRecursivelyAllowedReturnsTrue()
    {
        $this->subject->setFileOperationPermissions(16);
        $this->assertTrue($this->subject->isDirectoryRemoveRecursivelyAllowed());
        $this->subject->setFileOperationPermissions(31);
        $this->assertTrue($this->subject->isDirectoryRemoveRecursivelyAllowed());
    }

    /**
     * @test
     */
    public function setDirectoryRemoveRecursivelyAllowedSetsDirectoryRemoveRecursivelyAllowed()
    {
        $this->subject->setFileOperationPermissions(0);
        $this->subject->setDirectoryRemoveRecursivelyAllowed(true);
        $this->assertTrue($this->subject->isDirectoryRemoveRecursivelyAllowed());
    }

    /**
     * @test
     */
    public function getIsDirectoryCopyAllowedReturnsFalse()
    {
        $this->subject->setFileOperationPermissions(0);
        $this->assertFalse($this->subject->isDirectoryCopyAllowed());
        $this->subject->setFileOperationPermissions(7);
        $this->assertFalse($this->subject->isDirectoryCopyAllowed());
        $this->subject->setFileOperationPermissions(23);
        $this->assertFalse($this->subject->isDirectoryCopyAllowed());
    }

    /**
     * @test
     */
    public function getIsDirectoryCopyAllowedReturnsTrue()
    {
        $this->subject->setFileOperationPermissions(8);
        $this->assertTrue($this->subject->isDirectoryCopyAllowed());
        $this->subject->setFileOperationPermissions(15);
        $this->assertTrue($this->subject->isDirectoryCopyAllowed());
    }

    /**
     * @test
     */
    public function setDirectoryCopyAllowedSetsDirectoryCopyAllowed()
    {
        $this->subject->setFileOperationPermissions(0);
        $this->subject->setDirectoryCopyAllowed(true);
        $this->assertTrue($this->subject->isDirectoryCopyAllowed());
    }

    /**
     * @test
     */
    public function getIsDirectoryOperationAllowedReturnsFalse()
    {
        $this->subject->setFileOperationPermissions(0);
        $this->assertFalse($this->subject->isDirectoryOperationAllowed());
        $this->subject->setFileOperationPermissions(3);
        $this->assertFalse($this->subject->isDirectoryOperationAllowed());
        $this->subject->setFileOperationPermissions(11);
        $this->assertFalse($this->subject->isDirectoryOperationAllowed());
    }

    /**
     * @test
     */
    public function getIsDirectoryOperationAllowedReturnsTrue()
    {
        $this->subject->setFileOperationPermissions(4);
        $this->assertTrue($this->subject->isDirectoryOperationAllowed());
        $this->subject->setFileOperationPermissions(7);
        $this->assertTrue($this->subject->isDirectoryOperationAllowed());
    }

    /**
     * @test
     */
    public function setDirectoryOperationAllowedSetsDirectoryOperationAllowed()
    {
        $this->subject->setFileOperationPermissions(0);
        $this->subject->setDirectoryoperationAllowed(true);
        $this->assertTrue($this->subject->isDirectoryOperationAllowed());
    }

    /**
     * @test
     */
    public function getLockToDomainInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getLockToDomain());
    }

    /**
     * @test
     */
    public function setLockToDomainSetsLockToDomain()
    {
        $lockToDomain = 'foo.bar';
        $this->subject->setLockToDomain($lockToDomain);
        $this->assertSame($lockToDomain, $this->subject->getLockToDomain());
    }

    /**
     * @test
     */
    public function getHideInListInitiallyReturnsFalse()
    {
        $this->assertFalse($this->subject->getHideInList());
    }

    /**
     * @test
     */
    public function setHideInListSetsHideInList()
    {
        $this->subject->setHideInList(true);
        $this->assertTrue($this->subject->getHideInList());
    }

    /**
     * @test
     */
    public function getTsConfigInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getTsConfig());
    }

    /**
     * @test
     */
    public function setTsConfigSetsTsConfig()
    {
        $tsConfig = 'foo bar';
        $this->subject->setTsConfig($tsConfig);
        $this->assertSame($tsConfig, $this->subject->getTsConfig());
    }
}
