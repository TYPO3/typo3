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
 * @version $Id: LazyObjectStorage.php 1729 2009-11-25 21:37:20Z stucki $
 */
class Tx_Extbase_Persistence_LazyObjectStorage extends Tx_Extbase_Persistence_ObjectStorage implements Tx_Extbase_Persistence_LoadingStrategyInterface {

	/**
	 * The object this property is contained in.
	 *
	 * @var object
	 */
	protected $parentObject;

	/**
	 * The name of the property represented by this proxy.
	 *
	 * @var string
	 */
	protected $propertyName;

	/**
	 * The raw field value.
	 *
	 * @var mixed
	 */
	protected $fieldValue;

	/**
	 *
	 * @var bool
	 */
	protected $isInitialized = FALSE;

	/**
	 * Constructs this proxy instance.
	 *
	 * @param object $parentObject The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @param mixed $fieldValue The raw field value.
	 */
	public function __construct($parentObject, $propertyName, $fieldValue) {
		$this->parentObject = $parentObject;
		$this->propertyName = $propertyName;
		$this->fieldValue = $fieldValue;
	}
	
	/**
	 * This is a function lazy load implementation. 
	 *
	 * @return void
	 */
	protected function initializeStorage() {
		if (!$this->isInitialized) {
			$dataMapper = Tx_Extbase_Dispatcher::getPersistenceManager()->getBackend()->getDataMapper();
			$objects = $dataMapper->fetchRelated($this->parentObject, $this->propertyName, $this->fieldValue, FALSE);
			$storage = array();
			foreach ($objects as $object) {
				$storage[spl_object_hash($object)] = $object;
			}
			$this->storage = $storage;
			$this->parentObject->_memorizeCleanState($this->propertyName);
			$this->isInitialized = TRUE;
		}
	}
	
	/**
	 * Counts the elements in the storage array
	 *
	 * @return void
	 */
	public function count() {
		$dataMapper = Tx_Extbase_Dispatcher::getPersistenceManager()->getBackend()->getDataMapper();
		$columnMap = $dataMapper->getDataMap(get_class($this->parentObject))->getColumnMap($this->propertyName);
		$numberOfElements = NULL;
		if ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
			$parentKeyFieldName = $columnMap->getParentKeyFieldName();
			$dataMapper = Tx_Extbase_Dispatcher::getPersistenceManager()->getBackend()->getDataMapper();
			$numberOfElements = $dataMapper->countRelated($this->parentObject, $this->propertyName, $this->fieldValue);
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