<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2012 Oliver Hader <oliver.hader@typo3.org>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * A file context object (File Abstraction Layer)
 *
 * @package Extbase
 * @subpackage Domain\Model
 * @scope prototype
 * @entity
 * @api
 */
class Tx_Extbase_Tests_Functional_Domain_Model_Fixture_FileContext extends Tx_Extbase_DomainObject_AbstractEntity {
	/**
	 * @var Tx_Extbase_Domain_Model_File
	 */
	protected $file;

	/**
	 * @var Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_File>
	 */
	protected $files;

	/**
	 * @var Tx_Extbase_Domain_Model_FileReference
	 */
	protected $fileReference;

	/**
	 * @var Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_FileReference>
	 */
	protected $fileReferences;

	/**
	 * @var Tx_Extbase_Domain_Model_StaticFileCollection
	 */
	protected $staticFileCollection;

	/**
	 * @var Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_StaticFileCollection>
	 */
	protected $staticFileCollections;

	/**
	 * @var Tx_Extbase_Domain_Model_FolderBasedFileCollection
	 */
	protected $folderBasedFileCollection;

	/**
	 * @var Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_FolderBasedFileCollection>
	 */
	protected $folderBasedFileCollections;

	/**
	 * @var Tx_Extbase_Domain_Model_Folder
	 */
	protected $folder;

	/**
	 * @var Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_Folder>
	 */
	protected $folders;

	public function __construct() {
		$this->files = new Tx_Extbase_Persistence_ObjectStorage();
		$this->fileReferences = new Tx_Extbase_Persistence_ObjectStorage();
		$this->staticFileCollections = new Tx_Extbase_Persistence_ObjectStorage();
		$this->folderBasedFileCollections = new Tx_Extbase_Persistence_ObjectStorage();
		$this->folders = new Tx_Extbase_Persistence_ObjectStorage();
	}

	/**
	 * FILE
	 */

	/**
	 * @return Tx_Extbase_Domain_Model_File
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * @param Tx_Extbase_Domain_Model_File $file
	 */
	public function setFile(Tx_Extbase_Domain_Model_File $file) {
		$this->file = $file;
	}

	/**
	 * @return Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_File>
	 */
	public function getFiles() {
		return $this->files;
	}

	/**
	 * @param Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_File> $files
	 */
	public function setFiles(Tx_Extbase_Persistence_ObjectStorage $files) {
		$this->files = $files;
	}

	/**
	 * @param Tx_Extbase_Domain_Model_File $file
	 */
	public function addFile(Tx_Extbase_Domain_Model_File $file) {
		$this->files->attach($file);
	}

	/**
	 * @param Tx_Extbase_Domain_Model_File $file
	 */
	public function removeFile(Tx_Extbase_Domain_Model_File $file) {
		$this->files->detach($file);
	}

	/**
	 * COLLECTION
	 */

	/**
	 * @return Tx_Extbase_Domain_Model_StaticFileCollection
	 */
	public function getStaticFileCollection() {
		return $this->staticFileCollection;
	}

	/**
	 * @param Tx_Extbase_Domain_Model_StaticFileCollection $staticFileCollection
	 */
	public function setStaticFileCollection(Tx_Extbase_Domain_Model_StaticFileCollection $staticFileCollection) {
		$this->staticFileCollection = $staticFileCollection;
	}

	/**
	 * @return Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_StaticFileCollection>
	 */
	public function getStaticFileCollections() {
		return $this->staticFileCollections;
	}

	/**
	 * @param Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_StaticFileCollection> $staticFileCollections
	 */
	public function setStaticFileCollections(Tx_Extbase_Persistence_ObjectStorage $staticFileCollections) {
		$this->staticFileCollections = $staticFileCollections;
	}

	/**
	 * @param Tx_Extbase_Domain_Model_StaticFileCollection $staticFileCollection
	 */
	public function addStaticFileCollection(Tx_Extbase_Domain_Model_StaticFileCollection $staticFileCollection) {
		$this->staticFileCollections->attach($staticFileCollection);
	}

	/**
	 * @param Tx_Extbase_Domain_Model_StaticFileCollection $staticFileCollection
	 */
	public function removeStaticFileCollection(Tx_Extbase_Domain_Model_StaticFileCollection $staticFileCollection) {
		$this->staticFileCollections->detach($staticFileCollection);
	}

	/**
	 * @return Tx_Extbase_Domain_Model_FolderBasedFileCollection
	 */
	public function getFolderBasedFileCollection() {
		return $this->folderBasedFileCollection;
	}

	/**
	 * @param Tx_Extbase_Domain_Model_FolderBasedFileCollection $folderBasedFileCollection
	 */
	public function setFolderBasedFileCollection(Tx_Extbase_Domain_Model_FolderBasedFileCollection $folderBasedFileCollection) {
		$this->folderBasedFileCollection = $folderBasedFileCollection;
	}

	/**
	 * @return Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_FolderBasedFileCollection>
	 */
	public function getFolderBasedFileCollections() {
		return $this->folderBasedFileCollections;
	}

	/**
	 * @param Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_FolderBasedFileCollection> $folderBasedFileCollections
	 */
	public function setFolderBasedFileCollections(Tx_Extbase_Persistence_ObjectStorage $folderBasedFileCollections) {
		$this->folderBasedFileCollections = $folderBasedFileCollections;
	}

	/**
	 * @param Tx_Extbase_Domain_Model_FolderBasedFileCollection $folderBasedFileCollection
	 */
	public function addFolderBasedFileCollection(Tx_Extbase_Domain_Model_FolderBasedFileCollection $folderBasedFileCollection) {
		$this->folderBasedFileCollections->attach($folderBasedFileCollection);
	}

	/**
	 * @param Tx_Extbase_Domain_Model_FolderBasedFileCollection $folderBasedFileCollection
	 */
	public function removeFolderBasedFileCollection(Tx_Extbase_Domain_Model_FolderBasedFileCollection $folderBasedFileCollection) {
		$this->folderBasedFileCollections->detach($folderBasedFileCollection);
	}

	/**
	 * REFERENCE
	 */

	/**
	 * @return Tx_Extbase_Domain_Model_FileReference
	 */
	public function getFileReference() {
		return $this->fileReference;
	}

	/**
	 * @param Tx_Extbase_Domain_Model_FileReference $fileReference
	 */
	public function setFileReference(Tx_Extbase_Domain_Model_FileReference $fileReference) {
		$this->fileReference = $fileReference;
	}

	/**
	 * @return Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_FileReference>
	 */
	public function getFileReferences() {
		return $this->fileReferences;
	}

	/**
	 * @param Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_FileReference> $fileReferences
	 */
	public function setFileReferences(Tx_Extbase_Persistence_ObjectStorage $fileReferences) {
		$this->fileReferences = $fileReferences;
	}

	/**
	 * @param Tx_Extbase_Domain_Model_FileReference $fileReference
	 */
	public function addFileReference(Tx_Extbase_Domain_Model_FileReference $fileReference) {
		$this->fileReferences->attach($fileReference);
	}

	/**
	 * @param Tx_Extbase_Domain_Model_FileReference $fileReference
	 */
	public function removeFileReference(Tx_Extbase_Domain_Model_FileReference $fileReference) {
		$this->fileReferences->detach($fileReference);
	}

	/**
	 * FOLDER
	 */

	/**
	 * @return Tx_Extbase_Domain_Model_Folder
	 */
	public function getFolder() {
		return $this->folder;
	}

	/**
	 * @param Tx_Extbase_Domain_Model_Folder $folder
	 */
	public function setFolder(Tx_Extbase_Domain_Model_Folder $folder) {
		$this->folder = $folder;
	}

	/**
	 * @return Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_Folder>
	 */
	public function getFolders() {
		return $this->folders;
	}

	/**
	 * @param Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_Folder> $folders
	 */
	public function setFolders(Tx_Extbase_Persistence_ObjectStorage $folders) {
		$this->folders = $folders;
	}

	/**
	 * @param Tx_Extbase_Domain_Model_Folder $folder
	 */
	public function addFolder(Tx_Extbase_Domain_Model_Folder $folder) {
		$this->folders->attach($folder);
	}

	/**
	 * @param Tx_Extbase_Domain_Model_Folder $folder
	 */
	public function removeFolder(Tx_Extbase_Domain_Model_Folder $folder) {
		$this->folders->detach($folder);
	}
}
?>