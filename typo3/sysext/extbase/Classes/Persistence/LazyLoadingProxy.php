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
// TODO Implement support for CountableInterface
class Tx_Extbase_Persistence_LazyLoadingProxy {

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
	 *
	 * @var Tx_Extbase_Persistence_Mapper_DataMap
	 */
	private $dataMap;

	/**
	 * Constructs this proxy instance.
	 *
	 * @param object $parentObject The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @param Tx_Extbase_Persistence_Mapper_DataMap $dataMap The corresponding Data Map of the property
	 * @internal
	 */
	public function __construct($parentObject, $propertyName, Tx_Extbase_Persistence_Mapper_DataMap $dataMap) {
		$this->queryFactory = t3lib_div::makeInstance('Tx_Extbase_Persistence_QueryFactory');
		$this->parentObject = $parentObject;
		$this->propertyName = $propertyName;
		$this->dataMap = $dataMap;
	}

	/**
	 * Populate this proxy by asking the $population closure.
	 *
	 * @return object The instance (hopefully) returned
	 * @internal
	 */
	public function _loadRealInstance() {
		$result = NULL;
		$columnMap = $this->dataMap->getColumnMap($this->propertyName);
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
		// TODO This if statement should be further encapsulated to follow the DRY principle (see Data Mapper)
		if ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_ONE) {
			$query = $this->queryFactory->create($columnMap->getChildClassName(), FALSE);
			$result = current($query->matching($query->withUid($row[$columnMap->getColumnName()]))->execute());
		} elseif ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
			$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
			$query = $this->queryFactory->create($columnMap->getChildClassName(), FALSE);
			$parentKeyFieldName = $columnMap->getParentKeyFieldName();
			if (isset($parentKeyFieldName)) {
				$objects = $query->matching($query->equals($columnMap->getParentKeyFieldName(), $this->parentObject->getUid()))->execute();
			} else {
				$propertyValue = $row[$propertyName];
				$objects = $query->matching($query->withUid((int)$propertyValue))->execute();
			}
			foreach ($objects as $object) {
				$objectStorage->attach($object);
			}
			$result = $objectStorage;
		} elseif ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
			$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();
			$relationTableName = $columnMap->getRelationTableName();
			$left = $this->QOMFactory->selector($relationTableName);
			$childTableName = $columnMap->getChildTableName();
			$right = $this->QOMFactory->selector($childTableName);
			$joinCondition = $this->QOMFactory->equiJoinCondition($relationTableName, $columnMap->getChildKeyFieldName(), $childTableName, 'uid');
			$source = $this->QOMFactory->join(
				$left,
				$right,
				Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_JOIN_TYPE_INNER,
				$joinCondition
				);
			$query = $this->queryFactory->create($columnMap->getChildClassName(), FALSE);
			$query->setSource($source);
			$objects = $query->matching($query->equals($columnMap->getParentKeyFieldName(), $this->parentObject->getUid()))->execute();
			foreach ($objects as $object) {
				$objectStorage->attach($object);
			}
			$result = $objectStorage;
		}
		$this->parentObject->_setProperty($this->propertyName, $result);
		$this->parentObject->_memorizeCleanState($this->propertyName);
		return $result;
	}

	/**
	 * Magic method call implementation.
	 *
	 * @param string $methodName The name of the property to get
	 * @param array $arguments The arguments given to the call
	 * @return mixed
	 * @internal
	 */
	public function __call($methodName, $arguments) {
		$realInstance = $this->_loadRealInstance();
		return call_user_func_array(array($realInstance, $methodName), $arguments);
	}

	/**
	 * Magic get call implementation.
	 *
	 * @param string $propertyName The name of the property to get
	 * @return mixed
	 * @internal
	 */
	public function __get($propertyName) {
		$realInstance = $this->_loadRealInstance();
		return $realInstance->$propertyName;
	}

	/**
	 * Magic set call implementation.
	 *
	 * @param string $propertyName The name of the property to set
	 * @param mixed $value The value for the property to set
	 * @return void
	 * @internal
	 */
	public function __set($propertyName, $value) {
		$realInstance = $this->_loadRealInstance();
		$realInstance->$propertyName = $value;
	}

	/**
	 * Magic isset call implementation.
	 *
	 * @param string $propertyName The name of the property to check
	 * @return boolean
	 * @internal
	 */
	public function __isset($propertyName) {
		$realInstance = $this->_loadRealInstance();
		return isset($realInstance->$propertyName);
	}

	/**
	 * Magic unset call implementation.
	 *
	 * @param string $propertyName The name of the property to unset
	 * @return void
	 * @internal
	 */
	public function __unset($propertyName) {
		$realInstance = $this->_loadRealInstance();
		unset($realInstance->$propertyName);
	}
}
?>