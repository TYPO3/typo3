<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of FLOW3.
 *  All credits go to the v5 team.
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * A proxy that can replace any object and replaces itself in it's parent on
 * first access (call, get, set, isset, unset).
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: LazyLoadingProxy.php 2591 2009-06-09 19:23:47Z k-fish $
 */
class Tx_Extbase_Persistence_LazyObjectStorage extends Tx_Extbase_Persistence_ObjectStorage implements Tx_Extbase_Persistence_LoadingStrategyInterface {

	/**
	 * @var Tx_Extbase_Persistence_QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * The object this property is contained in.
	 *
	 * @var object
	 */
	private $parentObject;

	/**
	 * The name of the property represented by this proxy.
	 *
	 * @var string
	 */
	private $propertyName;

	/**
	 * The raw field value.
	 *
	 * @var mixed
	 */
	private $fieldValue;

	/**
	 *
	 * @var Tx_Extbase_Persistence_Mapper_ColumnMap
	 */
	private $columnMap;

	/**
	 * Constructs this proxy instance.
	 *
	 * @param object $parentObject The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @param mixed $fieldValue The raw field value.
	 * @param Tx_Extbase_Persistence_Mapper_DataMap $dataMap The corresponding Data Map of the property
	 */
	public function __construct($parentObject, $propertyName, $fieldValue, Tx_Extbase_Persistence_Mapper_ColumnMap $columnMap) {
		$this->queryFactory = t3lib_div::makeInstance('Tx_Extbase_Persistence_QueryFactory');
		$this->parentObject = $parentObject;
		$this->propertyName = $propertyName;
		$this->fieldValue = $fieldValue;
		$this->columnMap = $columnMap;
		$this->storage = NULL; // TODO
	}
	
	/**
	 * This is a function lazy load implementation. 
	 *
	 * @return void
	 */
	protected function initializeStorage() {
		if (is_null($this->storage)) {
			$dataMapper = Tx_Extbase_Dispatcher::getPersistenceManager()->getBackend()->getDataMapper();
			$objects = $dataMapper->fetchRelatedObjects($this->parentObject, $this->propertyName, $this->fieldValue, $this->columnMap);
			$storage = array();
			foreach ($objects as $object) {
				$storage[spl_object_hash($object)] = $object;
			}
			$this->storage = $storage;
			$this->parentObject->_memorizeCleanState($this->propertyName);
		}
		if (!is_array($this->storage)) {
			throw new Tx_Extbase_Persistence_Exception('The storage could not be initialized.', 1252393014); // TODO
		}
	}
	
	/**
	 * Counts the elements in the storage array
	 *
	 * @return void
	 */
	public function count() {
		$numberOfElements = NULL;
		if ($this->columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
			$parentKeyFieldName = $this->columnMap->getParentKeyFieldName();
			if (!empty($parentKeyFieldName)) {
				$numberOfElements = $this->fieldValue; // The field value in TYPO3 normally contains the number of related elements
			} else {
				$this->initializeStorage();
				$numberOfElements = count($this->storage);
				// FIXME Count on comma separated lists does not respect hidden objects
				// if (empty($this->fieldValue)) {
				// 	$numberOfElements = 0;
				// }
				// $numberOfElements = count(explode(',', $this->fieldValue));
			}
		} elseif ($this->columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
			$numberOfElements = $this->fieldValue;			
		} else {
			$this->initializeStorage();
			$numberOfElements = count($this->storage);			
		}
		if (is_null($numberOfElements)) {
			throw new Tx_Extbase_Persistence_Exception('The number of elements could not be determined.', 1252514486);
		}
		return $numberOfElements;
	}


}
?>