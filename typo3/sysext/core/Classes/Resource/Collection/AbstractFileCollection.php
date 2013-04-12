<?php
namespace TYPO3\CMS\Core\Resource\Collection;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Steffen Ritter <steffen.ritter@typo3.org>
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
 * Abstract collection.
 *
 * @author Steffen Ritter <steffen.ritter@typo3.org>
 */
abstract class AbstractFileCollection extends \TYPO3\CMS\Core\Collection\AbstractRecordCollection {

	/**
	 * The table name collections are stored to
	 *
	 * @var string
	 */
	static protected $storageTableName = 'sys_file_collection';

	/**
	 * The type of file collection
	 * (see \TYPO3\CMS\Core\Collection\RecordCollectionRepository::TYPE constants)
	 *
	 * @var string
	 */
	static protected $type;

	/**
	 * The name of the field items are handled with
	 * (usually either criteria, items or folder)
	 *
	 * @var string
	 */
	static protected $itemsCriteriaField;

	/**
	 * Field contents of $itemsCriteriaField. Defines which the items or search criteria for the items
	 * depending on the type (see self::$type above) of this file collection.
	 *
	 * @var mixed
	 */
	protected $itemsCriteria;

	/**
	 * Name of the table records of this collection are stored in
	 *
	 * @var string
	 */
	protected $itemTableName = 'sys_file';

	/**
	 * Sets the description.
	 *
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * Sets the title.
	 *
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Return the key of the current element
	 *
	 * @return string
	 */
	public function key() {
		/** @var $currentRecord \TYPO3\CMS\Core\Resource\File */
		$currentRecord = $this->storage->current();
		return $currentRecord->getIdentifier();
	}

	/**
	 * Generates comma-separated list of entry uids for usage in TCEmain
	 *
	 * @param boolean $includeTableName
	 * @return string
	 */
	protected function getItemUidList($includeTableName = FALSE) {
		$list = array();
		/** @var $entry \TYPO3\CMS\Core\Resource\File */
		foreach ($this->storage as $entry) {
			$list[] = $this->getItemTableName() . '_' . $entry->getUid();
		}
		return implode(',', $list);
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
			'type' => static::$type,
			'description' => $this->getDescription(),
			static::$itemsCriteriaField => $this->getItemsCriteria()
		);
	}

	/**
	 * Similar to method in \TYPO3\CMS\Core\Collection\AbstractRecordCollection,
	 * but without 'table_name' => $this->getItemTableName()
	 *
	 * @return array
	 */
	public function toArray() {
		$itemArray = array();
		/** @var $item \TYPO3\CMS\Core\Resource\File */
		foreach ($this->storage as $item) {
			$itemArray[] = $item->toArray();
		}
		return array(
			'uid' => $this->getIdentifier(),
			'title' => $this->getTitle(),
			'description' => $this->getDescription(),
			'items' => $itemArray
		);
	}

	/**
	 * Gets the current available items.
	 *
	 * @return array
	 */
	public function getItems() {
		$itemArray = array();
		/** @var $item \TYPO3\CMS\Core\Resource\File */
		foreach ($this->storage as $item) {
			$itemArray[] = $item;
		}
		return $itemArray;
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
		$this->itemsCriteria = $array[static::$itemsCriteriaField];
	}

	/**
	 * Gets ths items criteria.
	 *
	 * @return mixed
	 */
	public function getItemsCriteria() {
		return $this->itemsCriteria;
	}

	/**
	 * Sets the items criteria.
	 *
	 * @param mixed $itemsCriteria
	 */
	public function setItemsCriteria($itemsCriteria) {
		$this->itemsCriteria = $itemsCriteria;
	}

	/**
	 * Adds a file to this collection.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $data
	 */
	public function add(\TYPO3\CMS\Core\Resource\FileInterface $data) {
		$this->storage->push($data);
	}

	/**
	 * Adds all files of another collection to the corrent one.
	 *
	 * @param \TYPO3\CMS\Core\Collection\CollectionInterface $other
	 */
	public function addAll(\TYPO3\CMS\Core\Collection\CollectionInterface $other) {
		/** @var $value \TYPO3\CMS\Core\Resource\File */
		foreach ($other as $value) {
			$this->add($value);
		}
	}

	/**
	 * Removes a file from this collection.
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 */
	public function remove(\TYPO3\CMS\Core\Resource\File $file) {
		$offset = 0;
		/** @var $value \TYPO3\CMS\Core\Resource\File */
		foreach ($this->storage as $value) {
			if ($value === $file) {
				break;
			}
			$offset++;
		}
		$this->storage->offsetUnset($offset);
	}

	/**
	 * Removes all elements of the current collection.
	 */
	public function removeAll() {
		$this->storage = new \SplDoublyLinkedList();
	}

}


?>