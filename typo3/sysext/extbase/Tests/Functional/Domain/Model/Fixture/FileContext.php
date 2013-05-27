<?php
namespace TYPO3\CMS\Extbase\Tests\Functional\Domain\Model\Fixture;

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
 * @api
 */
class FileContext extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Model\File
	 */
	protected $file;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\File>
	 */
	protected $files;

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
	 */
	protected $fileReference;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
	 */
	protected $fileReferences;

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Model\StaticFileCollection
	 */
	protected $staticFileCollection;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\StaticFileCollection>
	 */
	protected $staticFileCollections;

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Model\FolderBasedFileCollection
	 */
	protected $folderBasedFileCollection;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FolderBasedFileCollection>
	 */
	protected $folderBasedFileCollections;

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Model\Folder
	 */
	protected $folder;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Folder>
	 */
	protected $folders;

	public function __construct() {
		$this->files = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->fileReferences = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->staticFileCollections = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->folderBasedFileCollections = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->folders = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * FILE
	 */
	/**
	 * @return \TYPO3\CMS\Extbase\Domain\Model\File
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Domain\Model\File $file
	 */
	public function setFile(\TYPO3\CMS\Extbase\Domain\Model\File $file) {
		$this->file = $file;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\File>
	 */
	public function getFiles() {
		return $this->files;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\File> $files
	 */
	public function setFiles(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $files) {
		$this->files = $files;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Domain\Model\File $file
	 */
	public function addFile(\TYPO3\CMS\Extbase\Domain\Model\File $file) {
		$this->files->attach($file);
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Domain\Model\File $file
	 */
	public function removeFile(\TYPO3\CMS\Extbase\Domain\Model\File $file) {
		$this->files->detach($file);
	}

	/**
	 * COLLECTION
	 */
	/**
	 * @return \TYPO3\CMS\Extbase\Domain\Model\StaticFileCollection
	 */
	public function getStaticFileCollection() {
		return $this->staticFileCollection;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Domain\Model\StaticFileCollection $staticFileCollection
	 */
	public function setStaticFileCollection(\TYPO3\CMS\Extbase\Domain\Model\StaticFileCollection $staticFileCollection) {
		$this->staticFileCollection = $staticFileCollection;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\StaticFileCollection>
	 */
	public function getStaticFileCollections() {
		return $this->staticFileCollections;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\StaticFileCollection> $staticFileCollections
	 */
	public function setStaticFileCollections(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $staticFileCollections) {
		$this->staticFileCollections = $staticFileCollections;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Domain\Model\StaticFileCollection $staticFileCollection
	 */
	public function addStaticFileCollection(\TYPO3\CMS\Extbase\Domain\Model\StaticFileCollection $staticFileCollection) {
		$this->staticFileCollections->attach($staticFileCollection);
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Domain\Model\StaticFileCollection $staticFileCollection
	 */
	public function removeStaticFileCollection(\TYPO3\CMS\Extbase\Domain\Model\StaticFileCollection $staticFileCollection) {
		$this->staticFileCollections->detach($staticFileCollection);
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Domain\Model\FolderBasedFileCollection
	 */
	public function getFolderBasedFileCollection() {
		return $this->folderBasedFileCollection;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Domain\Model\FolderBasedFileCollection $folderBasedFileCollection
	 */
	public function setFolderBasedFileCollection(\TYPO3\CMS\Extbase\Domain\Model\FolderBasedFileCollection $folderBasedFileCollection) {
		$this->folderBasedFileCollection = $folderBasedFileCollection;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FolderBasedFileCollection>
	 */
	public function getFolderBasedFileCollections() {
		return $this->folderBasedFileCollections;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FolderBasedFileCollection> $folderBasedFileCollections
	 */
	public function setFolderBasedFileCollections(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $folderBasedFileCollections) {
		$this->folderBasedFileCollections = $folderBasedFileCollections;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Domain\Model\FolderBasedFileCollection $folderBasedFileCollection
	 */
	public function addFolderBasedFileCollection(\TYPO3\CMS\Extbase\Domain\Model\FolderBasedFileCollection $folderBasedFileCollection) {
		$this->folderBasedFileCollections->attach($folderBasedFileCollection);
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Domain\Model\FolderBasedFileCollection $folderBasedFileCollection
	 */
	public function removeFolderBasedFileCollection(\TYPO3\CMS\Extbase\Domain\Model\FolderBasedFileCollection $folderBasedFileCollection) {
		$this->folderBasedFileCollections->detach($folderBasedFileCollection);
	}

	/**
	 * REFERENCE
	 */
	/**
	 * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference
	 */
	public function getFileReference() {
		return $this->fileReference;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $fileReference
	 */
	public function setFileReference(\TYPO3\CMS\Extbase\Domain\Model\FileReference $fileReference) {
		$this->fileReference = $fileReference;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
	 */
	public function getFileReferences() {
		return $this->fileReferences;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference> $fileReferences
	 */
	public function setFileReferences(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $fileReferences) {
		$this->fileReferences = $fileReferences;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $fileReference
	 */
	public function addFileReference(\TYPO3\CMS\Extbase\Domain\Model\FileReference $fileReference) {
		$this->fileReferences->attach($fileReference);
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $fileReference
	 */
	public function removeFileReference(\TYPO3\CMS\Extbase\Domain\Model\FileReference $fileReference) {
		$this->fileReferences->detach($fileReference);
	}

	/**
	 * FOLDER
	 */
	/**
	 * @return \TYPO3\CMS\Extbase\Domain\Model\Folder
	 */
	public function getFolder() {
		return $this->folder;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Domain\Model\Folder $folder
	 */
	public function setFolder(\TYPO3\CMS\Extbase\Domain\Model\Folder $folder) {
		$this->folder = $folder;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Folder>
	 */
	public function getFolders() {
		return $this->folders;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\Folder> $folders
	 */
	public function setFolders(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $folders) {
		$this->folders = $folders;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Domain\Model\Folder $folder
	 */
	public function addFolder(\TYPO3\CMS\Extbase\Domain\Model\Folder $folder) {
		$this->folders->attach($folder);
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Domain\Model\Folder $folder
	 */
	public function removeFolder(\TYPO3\CMS\Extbase\Domain\Model\Folder $folder) {
		$this->folders->detach($folder);
	}
}

?>