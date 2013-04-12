<?php
namespace TYPO3\CMS\Core\Resource\Collection;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * A collection containing a a set files to be represented as a (virtual) folder.
 * This collection is persisted to the database with the accordant folder reference.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class FolderBasedFileCollection extends \TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection {

	/**
	 * @var string
	 */
	static protected $storageTableName = 'sys_file_collection';

	/**
	 * @var string
	 */
	static protected $type = 'folder';

	/**
	 * @var string
	 */
	static protected $itemsCriteriaField = 'folder';

	/**
	 * The folder
	 *
	 * @var \TYPO3\CMS\Core\Resource\Folder
	 */
	protected $folder;

	/**
	 * Populates the content-entries of the storage
	 *
	 * Queries the underlying storage for entries of the collection
	 * and adds them to the collection data.
	 *
	 * If the content entries of the storage had not been loaded on creation
	 * ($fillItems = false) this function is to be used for loading the contents
	 * afterwards.
	 *
	 * @return void
	 */
	public function loadContents() {
		$entries = $this->folder->getFiles();
		foreach ($entries as $entry) {
			$this->add($entry);
		}
	}

	/**
	 * Gets the items criteria.
	 *
	 * @return string
	 */
	public function getItemsCriteria() {
		return $this->folder->getCombinedIdentifier();
	}

	/**
	 * Returns an array of the persistable properties and contents
	 * which are processable by TCEmain.
	 *
	 * @return array
	 */
	protected function getPersistableDataArray() {
		return array(
			'title' => $this->getTitle(),
			'type' => self::$type,
			'description' => $this->getDescription(),
			'folder' => $this->folder->getIdentifier(),
			'storage' => $this->folder->getStorage()->getUid()
		);
	}

	/**
	 * Similar to method in \TYPO3\CMS\Core\Collection\AbstractRecordCollection,
	 * but without $this->itemTableName= $array['table_name'],
	 * but with $this->storageItemsFieldContent = $array[self::$storageItemsField];
	 *
	 * @param array $array
	 */
	public function fromArray(array $array) {
		$this->uid = $array['uid'];
		$this->title = $array['title'];
		$this->description = $array['description'];
		if (!empty($array['folder']) && !empty($array['storage'])) {
			/** @var $storageRepository \TYPO3\CMS\Core\Resource\StorageRepository */
			$storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
			/** @var $storage \TYPO3\CMS\Core\Resource\ResourceStorage */
			$storage = $storageRepository->findByUid($array['storage']);
			if ($storage) {
				$this->folder = $storage->getFolder($array['folder']);
			}
		}
	}

}


?>