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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BackendUserGroupTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new BackendUserGroup();
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function setTitleSetsTitle(): void
    {
        $title = 'foo bar';
        $this->subject->setTitle($title);
        self::assertSame($title, $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function getDescriptionInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getDescription());
    }

    /**
     * @test
     */
    public function setDescriptionSetsDescription(): void
    {
        $description = 'foo bar';
        $this->subject->setDescription($description);
        self::assertSame($description, $this->subject->getDescription());
    }

    /**
     * @test
     */
    public function setSubGroupsSetsSubgroups(): void
    {
        $subGroups = new ObjectStorage();
        $this->subject->setSubGroups($subGroups);
        self::assertSame($subGroups, $this->subject->getSubGroups());
    }

    /**
     * @test
     */
    public function anSubGroupCanBeRemoved(): void
    {
        $group1 = new BackendUserGroup();
        $group1->setTitle('foo');
        $group2 = new BackendUserGroup();
        $group2->setTitle('bar');
        $this->subject->addSubGroup($group1);
        $this->subject->addSubGroup($group2);
        self::assertCount(2, $this->subject->getSubGroups());
        $this->subject->removeSubGroup($group1);
        self::assertCount(1, $this->subject->getSubGroups());
        $this->subject->removeSubGroup($group2);
        self::assertCount(0, $this->subject->getSubGroups());
    }

    /**
     * @test
     */
    public function allSubGroupsCanBeRemoved(): void
    {
        $group1 = new BackendUserGroup();
        $group1->setTitle('foo');
        $group2 = new BackendUserGroup();
        $group2->setTitle('bar');
        $this->subject->addSubGroup($group1);
        $this->subject->addSubGroup($group2);
        $this->subject->removeAllSubGroups();
        self::assertCount(0, $this->subject->getSubGroups());
    }

    /**
     * @test
     */
    public function getModulesInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getModules());
    }

    /**
     * @test
     */
    public function setModulesSetsModules(): void
    {
        $modules = 'foo,bar';
        $this->subject->setModules($modules);
        self::assertSame($modules, $this->subject->getModules());
    }

    /**
     * @test
     */
    public function getTablesListeningInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getTablesListening());
    }

    /**
     * @test
     */
    public function setTablesListeningSetsTablesListening(): void
    {
        $tablesListening = 'foo,bar';
        $this->subject->setTablesListening($tablesListening);
        self::assertSame($tablesListening, $this->subject->getTablesListening());
    }

    /**
     * @test
     */
    public function getTablesModifyInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getTablesModify());
    }

    /**
     * @test
     */
    public function getPageTypesInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getPageTypes());
    }

    /**
     * @test
     */
    public function setPageTypesSetsPageTypes(): void
    {
        $pageTypes = 'foo,bar';
        $this->subject->setPageTypes($pageTypes);
        self::assertSame($pageTypes, $this->subject->getPageTypes());
    }

    /**
     * @test
     */
    public function setTablesModifySetsTablesModify(): void
    {
        $tablesModify = 'foo,bar';
        $this->subject->setTablesModify($tablesModify);
        self::assertSame($tablesModify, $this->subject->getTablesModify());
    }

    /**
     * @test
     */
    public function getAllowedExcludeFieldsInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getAllowedExcludeFields());
    }

    /**
     * @test
     */
    public function setAllowedExcludeFieldsSetsAllowedExcludeFields(): void
    {
        $allowedExcludeFields = 'foo,bar';
        $this->subject->setAllowedExcludeFields($allowedExcludeFields);
        self::assertSame($allowedExcludeFields, $this->subject->getAllowedExcludeFields());
    }

    /**
     * @test
     */
    public function getExplicitlyAllowAndDenyInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getExplicitlyAllowAndDeny());
    }

    /**
     * @test
     */
    public function setExplicitlyAllowAndDenySetsExplicitlyAllowAndDeny(): void
    {
        $explicitlyAllowAndDeny = 'foo,bar';
        $this->subject->setExplicitlyAllowAndDeny($explicitlyAllowAndDeny);
        self::assertSame($explicitlyAllowAndDeny, $this->subject->getExplicitlyAllowAndDeny());
    }

    /**
     * @test
     */
    public function getAllowedLanguagesInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getAllowedLanguages());
    }

    /**
     * @test
     */
    public function setAllowedLanguagesSetsAllowedLanguages(): void
    {
        $allowedLanguages = '1,0';
        $this->subject->setAllowedLanguages($allowedLanguages);
        self::assertSame($allowedLanguages, $this->subject->getAllowedLanguages());
    }

    /**
     * @test
     */
    public function getWorkspacePermissionInitiallyReturnsFalse(): void
    {
        self::assertFalse($this->subject->getWorkspacePermission());
    }

    /**
     * @test
     */
    public function setWorkspacePermissionSetsWorkspacePermission(): void
    {
        $this->subject->setWorkspacePermissions(true);
        self::assertTrue($this->subject->getWorkspacePermission());
    }

    /**
     * @test
     */
    public function getDatabaseMountsInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getDatabaseMounts());
    }

    /**
     * @test
     */
    public function setDatabaseMountsSetsDatabaseMounts(): void
    {
        $mounts = '1,42';
        $this->subject->setDatabaseMounts($mounts);
        self::assertSame($mounts, $this->subject->getDatabaseMounts());
    }

    /**
     * @test
     */
    public function getFileOperationPermissionsInitiallyReturnsZero(): void
    {
        self::assertSame(0, $this->subject->getFileOperationPermissions());
    }

    /**
     * @test
     */
    public function setFileOperationPermissionsSetsFileOperationPermissions(): void
    {
        $permission = 7;
        $this->subject->setFileOperationPermissions($permission);
        self::assertSame($permission, $this->subject->getFileOperationPermissions());
    }

    /**
     * @test
     */
    public function getIsFileOperationAllowedReturnsFalse(): void
    {
        $this->subject->setFileOperationPermissions(0);
        self::assertFalse($this->subject->isFileOperationAllowed());
        $this->subject->setFileOperationPermissions(2);
        self::assertFalse($this->subject->isFileOperationAllowed());
        $this->subject->setFileOperationPermissions(6);
        self::assertFalse($this->subject->isFileOperationAllowed());
    }

    /**
     * @test
     */
    public function getIsFileOperationAllowedReturnsTrue(): void
    {
        $this->subject->setFileOperationPermissions(1);
        self::assertTrue($this->subject->isFileOperationAllowed());
        $this->subject->setFileOperationPermissions(3);
        self::assertTrue($this->subject->isFileOperationAllowed());
    }

    /**
     * @test
     */
    public function setFileOperationAllowedSetsFileOperationAllowed(): void
    {
        $this->subject->setFileOperationPermissions(0);
        $this->subject->setFileOperationAllowed(true);
        self::assertTrue($this->subject->isFileOperationAllowed());
    }

    /**
     * @test
     */
    public function getIsDirectoryRemoveRecursivelyAllowedReturnsFalse(): void
    {
        $this->subject->setFileOperationPermissions(1);
        self::assertFalse($this->subject->isDirectoryRemoveRecursivelyAllowed());
        $this->subject->setFileOperationPermissions(15);
        self::assertFalse($this->subject->isDirectoryRemoveRecursivelyAllowed());
        $this->subject->setFileOperationPermissions(7);
        self::assertFalse($this->subject->isDirectoryRemoveRecursivelyAllowed());
    }

    /**
     * @test
     */
    public function getIsDirectoryRemoveRecursivelyAllowedReturnsTrue(): void
    {
        $this->subject->setFileOperationPermissions(16);
        self::assertTrue($this->subject->isDirectoryRemoveRecursivelyAllowed());
        $this->subject->setFileOperationPermissions(31);
        self::assertTrue($this->subject->isDirectoryRemoveRecursivelyAllowed());
    }

    /**
     * @test
     */
    public function setDirectoryRemoveRecursivelyAllowedSetsDirectoryRemoveRecursivelyAllowed(): void
    {
        $this->subject->setFileOperationPermissions(0);
        $this->subject->setDirectoryRemoveRecursivelyAllowed(true);
        self::assertTrue($this->subject->isDirectoryRemoveRecursivelyAllowed());
    }

    /**
     * @test
     */
    public function getIsDirectoryCopyAllowedReturnsFalse(): void
    {
        $this->subject->setFileOperationPermissions(0);
        self::assertFalse($this->subject->isDirectoryCopyAllowed());
        $this->subject->setFileOperationPermissions(7);
        self::assertFalse($this->subject->isDirectoryCopyAllowed());
        $this->subject->setFileOperationPermissions(23);
        self::assertFalse($this->subject->isDirectoryCopyAllowed());
    }

    /**
     * @test
     */
    public function getIsDirectoryCopyAllowedReturnsTrue(): void
    {
        $this->subject->setFileOperationPermissions(8);
        self::assertTrue($this->subject->isDirectoryCopyAllowed());
        $this->subject->setFileOperationPermissions(15);
        self::assertTrue($this->subject->isDirectoryCopyAllowed());
    }

    /**
     * @test
     */
    public function setDirectoryCopyAllowedSetsDirectoryCopyAllowed(): void
    {
        $this->subject->setFileOperationPermissions(0);
        $this->subject->setDirectoryCopyAllowed(true);
        self::assertTrue($this->subject->isDirectoryCopyAllowed());
    }

    /**
     * @test
     */
    public function getIsDirectoryOperationAllowedReturnsFalse(): void
    {
        $this->subject->setFileOperationPermissions(0);
        self::assertFalse($this->subject->isDirectoryOperationAllowed());
        $this->subject->setFileOperationPermissions(3);
        self::assertFalse($this->subject->isDirectoryOperationAllowed());
        $this->subject->setFileOperationPermissions(11);
        self::assertFalse($this->subject->isDirectoryOperationAllowed());
    }

    /**
     * @test
     */
    public function getIsDirectoryOperationAllowedReturnsTrue(): void
    {
        $this->subject->setFileOperationPermissions(4);
        self::assertTrue($this->subject->isDirectoryOperationAllowed());
        $this->subject->setFileOperationPermissions(7);
        self::assertTrue($this->subject->isDirectoryOperationAllowed());
    }

    /**
     * @test
     */
    public function setDirectoryOperationAllowedSetsDirectoryOperationAllowed(): void
    {
        $this->subject->setFileOperationPermissions(0);
        $this->subject->setDirectoryOperationAllowed(true);
        self::assertTrue($this->subject->isDirectoryOperationAllowed());
    }

    /**
     * @test
     */
    public function getTsConfigInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getTsConfig());
    }

    /**
     * @test
     */
    public function setTsConfigSetsTsConfig(): void
    {
        $tsConfig = 'foo bar';
        $this->subject->setTsConfig($tsConfig);
        self::assertSame($tsConfig, $this->subject->getTsConfig());
    }
}
