<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Steffen Ritter <typo3@steffen-ritter.net>
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
 * Implementation of a RecordCollection for static TCA-Records
 *
 * @author Steffen Ritter <typo3@steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_collection_StaticRecordCollection extends t3lib_collection_AbstractRecordCollection implements t3lib_collection_Editable {
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
		/** @var t3lib_TcaRelationService $relationService */
		$relationService = t3lib_div::makeInstance('t3lib_TcaRelationService', self::$storageTableName, self::$storageItemsField, $this->itemTableName);

		$entries = $relationService->getRecordsWithRelationFromCurrentRecord($this->toArray());
		$this->removeAll();
		foreach ($entries as $entry) {
			$this->add($entry);
		}
	}

	/**
	 * Returns an array of the persistable properties and contents
	 * which are processable by TCEmain.
	 *
	 * for internal usage in persist only.
	 *
	 * @return array
	 */
	protected function getPersistableDataArray() {
		return array(
			'title' => $this->getTitle(),
			'description' => $this->getDescription(),
			'items' => $this->getItemUidList(TRUE),
			'type' => 'static',
			'table_name' => $this->getItemTableName(),
		);
	}

	/**
	 * Adds on entry to the collection
	 *
	 * @param mixed $data
	 * @return void
	 */
	public function add($data) {
		$this->storage->push($data);
	}

	/**
	 * Adds a set of entries to the collection
	 *
	 * @param t3lib_collection_Collection $other
	 * @return void
	 */
	public function addAll(t3lib_collection_Collection $other) {
		foreach ($other as $value) {
			$this->add($value);
		}
	}

	/**
	 * Removes the given entry from collection
	 *
	 * Note: not the given "index"
	 *
	 * @param mixed $data
	 * @return void
	 */
	public function remove($data) {
		$offset = 0;
		foreach ($this->storage as $value) {
			if ($value == $data) {
				break;
			}
			$offset++;
		}
		$this->storage->offsetUnset($offset);
	}

	/**
	 * Removes all entries from the collection
	 *
	 * collection will be empty afterwards
	 *
	 * @abstract
	 * @return void
	 */
	public function removeAll() {
		$this->storage = new SplDoublyLinkedList();
	}
}
?>