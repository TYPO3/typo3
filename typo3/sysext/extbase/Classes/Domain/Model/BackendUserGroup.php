<?php
namespace TYPO3\CMS\Extbase\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * This model represents a backend usergroup.
 *
 * @api
 */
class BackendUserGroup extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	const FILE_OPPERATIONS = 1;
	const FILE_UNZIP = 2;
	const DIRECTORY_OPPERATIONS = 4;
	const DIRECTORY_COPY = 8;
	const DIRECTORY_REMOVE_RECURSIVELY = 16;

	/**
	 * @var string
	 * @validate notEmpty
	 */
	protected $title = '';

	/**
	 * @var string
	 */
	protected $description = '';

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup>
	 */
	protected $subGroups;

	/**
	 * @var string
	 */
	protected $modules = '';

	/**
	 * @var string
	 */
	protected $tablesListening = '';

	/**
	 * @var string
	 */
	protected $tablesModify = '';

	/**
	 * @var string
	 */
	protected $pageTypes = '';

	/**
	 * @var string
	 */
	protected $allowedExcludeFields = '';

	/**
	 * @var string
	 */
	protected $explicitlyAllowAndDeny = '';

	/**
	 * @var string
	 */
	protected $allowedLanguages = '';

	/**
	 * @var boolean
	 */
	protected $workspacePermission = FALSE;

	/**
	 * @var string
	 */
	protected $databaseMounts = '';

	/**
	 * @var integer
	 */
	protected $fileOperationPermissions = 0;

	/**
	 * @var string
	 */
	protected $lockToDomain = '';

	/**
	 * @var boolean
	 */
	protected $hideInList = FALSE;

	/**
	 * @var string
	 */
	protected $tsConfig = '';

	/**
	 * Constructs this backend usergroup
	 */
	public function __construct() {
		$this->subGroups = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Setter for title
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Getter for title
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Setter for description
	 *
	 * @param string $description
	 * @return void
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * Getter for description
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Setter for the sub groups
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $subGroups
	 * @return void
	 */
	public function setSubGroups(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $subGroups) {
		$this->subGroups = $subGroups;
	}

	/**
	 * Adds a sub group to this backend user group
	 *
	 * @param \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup $beGroup
	 * @return void
	 */
	public function addSubGroup(\TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup $beGroup) {
		$this->subGroups->attach($beGroup);
	}

	/**
	 * Removes sub group from this backend user group
	 *
	 * @param \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup $groupToDelete
	 * @return void
	 */
	public function removeSubGroup(\TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup $groupToDelete) {
		$this->subGroups->detach($groupToDelete);
	}

	/**
	 * Remove all sub groups from this backend user group
	 *
	 * @return void
	 */
	public function removeAllSubGroups() {
		$subGroups = clone $this->subGroups;
		$this->subGroups->removeAll($subGroups);
	}

	/**
	 * Getter of sub groups
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	public function getSubGroups() {
		return $this->subGroups;
	}

	/**
	 * Setter for modules
	 *
	 * @param string $modules
	 * @return void
	 */
	public function setModules($modules) {
		$this->modules = $modules;
	}

	/**
	 * Getter for modules
	 *
	 * @return string
	 */
	public function getModules() {
		return $this->modules;
	}

	/**
	 * Setter for tables listening
	 *
	 * @param string $tablesListening
	 * @return void
	 */
	public function setTablesListening($tablesListening) {
		$this->tablesListening = $tablesListening;
	}

	/**
	 * Getter for tables listening
	 *
	 * @return string
	 */
	public function getTablesListening() {
		return $this->tablesListening;
	}

	/**
	 * Setter for tables modify
	 *
	 * @param string $tablesModify
	 * @return void
	 */
	public function setTablesModify($tablesModify) {
		$this->tablesModify = $tablesModify;
	}

	/**
	 * Getter for tables modify
	 *
	 * @return string
	 */
	public function getTablesModify() {
		return $this->tablesModify;
	}

	/**
	 * Setter for page types
	 *
	 * @param string $pageTypes
	 * @return void
	 */
	public function setPageTypes($pageTypes) {
		$this->pageTypes = $pageTypes;
	}

	/**
	 * Getter for page types
	 *
	 * @return string
	 */
	public function getPageTypes() {
		return $this->pageTypes;
	}

	/**
	 * Setter for allowed exclude fields
	 *
	 * @param string $allowedExcludeFields
	 * @return void
	 */
	public function setAllowedExcludeFields($allowedExcludeFields) {
		$this->allowedExcludeFields = $allowedExcludeFields;
	}

	/**
	 * Getter for allowed exclude fields
	 *
	 * @return string
	 */
	public function getAllowedExcludeFields() {
		return $this->allowedExcludeFields;
	}

	/**
	 * Setter for explicitly allow and deny
	 *
	 * @param string $explicitlyAllowAndDeny
	 * @return void
	 */
	public function setExplicitlyAllowAndDeny($explicitlyAllowAndDeny) {
		$this->explicitlyAllowAndDeny = $explicitlyAllowAndDeny;
	}

	/**
	 * Getter for explicitly allow and deny
	 *
	 * @return string
	 */
	public function getExplicitlyAllowAndDeny() {
		return $this->explicitlyAllowAndDeny;
	}

	/**
	 * Setter for allowed languages
	 *
	 * @param string $allowedLanguages
	 * @return void
	 */
	public function setAllowedLanguages($allowedLanguages) {
		$this->allowedLanguages = $allowedLanguages;
	}

	/**
	 * Getter for allowed languages
	 *
	 * @return string
	 */
	public function getAllowedLanguages() {
		return $this->allowedLanguages;
	}

	/**
	 * Setter for workspace permission
	 *
	 * @param boolean $workspacePermission
	 * @return void
	 */
	public function setWorkspacePermissions($workspacePermission) {
		$this->workspacePermission = $workspacePermission;
	}

	/**
	 * Getter for workspace permission
	 *
	 * @return boolean
	 */
	public function getWorkspacePermission() {
		return $this->workspacePermission;
	}

	/**
	 * Setter for database mounts
	 *
	 * @param string $databaseMounts
	 * @return void
	 */
	public function setDatabaseMounts($databaseMounts) {
		$this->databaseMounts = $databaseMounts;
	}

	/**
	 * Getter for database mounts
	 *
	 * @return string
	 */
	public function getDatabaseMounts() {
		return $this->databaseMounts;
	}

	/**
	 * Getter for file operation permissions
	 *
	 * @param integer $fileOperationPermissions
	 * @return void
	 */
	public function setFileOperationPermissions($fileOperationPermissions) {
		$this->fileOperationPermissions = $fileOperationPermissions;
	}

	/**
	 * Getter for file operation permissions
	 *
	 * @return integer
	 */
	public function getFileOperationPermissions() {
		return $this->fileOperationPermissions;
	}

	/**
	 * Check if file operations like upload, copy, move, delete, rename, new and
	 * edit files is allowed.
	 *
	 * @return boolean
	 */
	public function isFileOperationAllowed() {
		return $this->isPermissionSet(self::FILE_OPPERATIONS);
	}

	/**
	 * Set the the bit for file operations are allowed.
	 *
	 * @param boolean $value
	 * @return void
	 */
	public function setFileOperationAllowed($value) {
		$this->setPermission(self::FILE_OPPERATIONS, $value);
	}

	/**
	 * Check if it is allowed to unzip files.
	 *
	 * @return boolean
	 */
	public function isFileUnzipAllowed() {
		return $this->isPermissionSet(self::FILE_UNZIP);
	}

	/**
	 * Set the the bit for unzip files are allowed.
	 *
	 * @param boolean $value
	 * @return void
	 */
	public function setFileUnzipAllowed($value) {
		$this->setPermission(self::FILE_UNZIP, $value);
	}

	/**
	 * Check if folder operations like move, delete, rename, and new are allowed.
	 *
	 * @return boolean
	 */
	public function isDirectoryOperationAllowed() {
		return $this->isPermissionSet(self::DIRECTORY_OPPERATIONS);
	}

	/**
	 * Set the the bit for directory operations are allowed.
	 *
	 * @param boolean $value
	 * @return void
	 */
	public function setDirectoryOperationAllowed($value) {
		$this->setPermission(self::DIRECTORY_OPPERATIONS, $value);
	}

	/**
	 * Check if it is allowed to copy folders.
	 *
	 * @return boolean
	 */
	public function isDirectoryCopyAllowed() {
		return $this->isPermissionSet(self::DIRECTORY_COPY);
	}

	/**
	 * Set the the bit for copy directories.
	 *
	 * @param boolean $value
	 * @return void
	 */
	public function setDirectoryCopyAllowed($value) {
		$this->setPermission(self::DIRECTORY_COPY, $value);
	}

	/**
	 * Check if it is allowed to remove folders recursively.
	 *
	 * @return boolean
	 */
	public function isDirectoryRemoveRecursivelyAllowed() {
		return $this->isPermissionSet(self::DIRECTORY_REMOVE_RECURSIVELY);
	}

	/**
	 * Set the the bit for remove directories recursively.
	 *
	 * @param boolean $value
	 * @return void
	 */
	public function setDirectoryRemoveRecursivelyAllowed($value) {
		$this->setPermission(self::DIRECTORY_REMOVE_RECURSIVELY, $value);
	}

	/**
	 * Setter for lock to domain
	 *
	 * @param string $lockToDomain
	 * @return void
	 */
	public function setLockToDomain($lockToDomain) {
		$this->lockToDomain = $lockToDomain;
	}

	/**
	 * Getter for lock to domain
	 *
	 * @return string
	 */
	public function getLockToDomain() {
		return $this->lockToDomain;
	}

	/**
	 * Setter for hide in list
	 *
	 * @param boolean $hideInList
	 * @return void
	 */
	public function setHideInList($hideInList) {
		$this->hideInList = $hideInList;
	}

	/**
	 * Getter for hide in list
	 *
	 * @return boolean
	 */
	public function getHideInList() {
		return $this->hideInList;
	}

	/**
	 * Setter for ts config
	 *
	 * @param string $tsConfig
	 */
	public function setTsConfig($tsConfig) {
		$this->tsConfig = $tsConfig;
	}

	/**
	 * Getter for ts config
	 *
	 * @return string
	 */
	public function getTsConfig() {
		return $this->tsConfig;
	}

	/**
	 * Helper method for checking the permissions bitwise.
	 *
	 * @param integer $permission
	 * @return boolean
	 */
	protected function isPermissionSet($permission) {
		return ($this->fileOperationPermissions & $permission) == $permission;
	}

	/**
	 * Helper method for settung permissions bitwise.
	 *
	 * @param integer $permission
	 * @param boolean $value
	 * @return void
	 */
	protected function setPermission($permission, $value) {
		if ($value) {
			$this->fileOperationPermissions |= $permission;
		} else {
			$this->fileOperationPermissions &= ~$permission;
		}
	}
}

?>