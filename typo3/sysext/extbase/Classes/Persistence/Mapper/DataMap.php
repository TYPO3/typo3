<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * A data map to map a single table configured in $TCA on a domain object.
 *
 * @package TYPO3
 * @subpackage extmvc
 * @version $ID:$
 */
class TX_EXTMVC_Persistence_Mapper_DataMap {
// SK: PHPDoc (even for getters and setters, sorry ;-) )
// SK: I did not do an in-depth check of this class
	/**
	 * The domain class name
	 *
	 * @var string
	 **/
	protected $className;

	/**
	 * The table name corresponding to the domain class configured in $TCA
	 *
	 * @var string
	 **/
	protected $tableName;

	/**
	 * An array of column maps configured in $TCA
	 *
	 * @var array
	 **/
	protected $columnMaps;

	/**
	 * Constructs this DataMap
	 *
	 * @param string $className The class name. This determines the table to fetch the configuration for
	 */
	public function __construct($className) {
		$this->setClassName($className);
		// SK: strtolower(..) is the wrong conversion for the class name. See the notice in the dispatcher (tt_news ->tx_ttnews)
		$this->setTableName(strtolower($this->className));
		t3lib_div::loadTCA($this->getTableName());
	}

	public function setClassName($className) {
		$this->className = $className;
	}

	public function getClassName() {
		return $this->className;
	}

	public function setTableName($tableName) {
		$this->tableName = $tableName;
	}

	public function getTableName() {
		return $this->tableName;
	}

	// SK: Why is initialize() not called in the constructor? Without initialize(), the object cannot do anything - or am I wrong here?
	public function initialize() {
		$columns = $GLOBALS['TCA'][$this->getTableName()]['columns'];
		if (is_array($columns)) {
			$this->addCommonColumns();
			foreach ($columns as $columnName => $columnConfiguration) {
				$columnMap = new TX_EXTMVC_Persistence_Mapper_ColumnMap($columnName, $this);
				$this->setTypeOfValue($columnMap, $columnConfiguration);
				// TODO support for IRRE
				// TODO support for MM_insert_fields and MM_match_fields
				// SK: Discuss the above things
				$this->setRelations($columnMap, $columnConfiguration);
				$this->addColumnMap($columnMap);
			}
		} else {
			// TODO Throw exception
		}
	}

	protected function addCommonColumns() {
		$this->addColumn('uid', TX_EXTMVC_Persistence_Mapper_ColumnMap::TYPE_INTEGER);
		$this->addColumn('pid', TX_EXTMVC_Persistence_Mapper_ColumnMap::TYPE_INTEGER);
		$this->addColumn('tstamp', TX_EXTMVC_Persistence_Mapper_ColumnMap::TYPE_DATE);
		$this->addColumn('crdate', TX_EXTMVC_Persistence_Mapper_ColumnMap::TYPE_DATE);
		$this->addColumn('cruser_id', TX_EXTMVC_Persistence_Mapper_ColumnMap::TYPE_INTEGER);
		if ($this->getDeletedColumnName() !== NULL) {
			$this->addColumn($this->getDeletedColumnName(), TX_EXTMVC_Persistence_Mapper_ColumnMap::TYPE_BOOLEAN);
		}
		if ($this->getHiddenColumnName() !== NULL) {
			$this->addColumn($this->getHiddenColumnName(), TX_EXTMVC_Persistence_Mapper_ColumnMap::TYPE_BOOLEAN);
		}
	}

	protected function setTypeOfValue(TX_EXTMVC_Persistence_Mapper_ColumnMap &$columnMap, $columnConfiguration) {
		if (strpos($columnConfiguration['config']['eval'], 'date') !== FALSE
			|| strpos($columnConfiguration['config']['eval'], 'datetime') !== FALSE) {
			$columnMap->setTypeOfValue(TX_EXTMVC_Persistence_Mapper_ColumnMap::TYPE_DATE);
		} elseif ($columnConfiguration['config']['type'] === 'check' && empty($columnConfiguration['config']['items'])) {
			$columnMap->setTypeOfValue(TX_EXTMVC_Persistence_Mapper_ColumnMap::TYPE_BOOLEAN);
		} elseif (strpos($columnConfiguration['config']['eval'], 'int') !== FALSE) {
			$columnMap->setTypeOfValue(TX_EXTMVC_Persistence_Mapper_ColumnMap::TYPE_INTEGER);
		} elseif (strpos($columnConfiguration['config']['eval'], 'double2') !== FALSE) {
			$columnMap->setTypeOfValue(TX_EXTMVC_Persistence_Mapper_ColumnMap::TYPE_FLOAT);
		} else {
			$columnMap->setTypeOfValue(TX_EXTMVC_Persistence_Mapper_ColumnMap::TYPE_STRING);
		}
	}

	protected function setRelations(TX_EXTMVC_Persistence_Mapper_ColumnMap &$columnMap, $columnConfiguration) {
		if (isset($columnConfiguration['config']['foreign_table']) && !isset($columnConfiguration['config']['MM'])) {
			if ($columnConfiguration['config']['maxitems'] == 1) {
				$columnMap->setTypeOfRelation(TX_EXTMVC_Persistence_Mapper_ColumnMap::RELATION_HAS_ONE);
				$columnMap->setChildClassName($columnConfiguration['config']['foreign_class']);
				$columnMap->setChildTableName($columnConfiguration['config']['foreign_table']);
				$columnMap->setChildTableWhere($columnConfiguration['config']['foreign_table_where']);
				$columnMap->setChildSortbyFieldName($columnConfiguration['config']['foreign_sortby']);
				$columnMap->setParentKeyFieldName($columnConfiguration['config']['foreign_field']);
				$columnMap->setParentTableFieldName($columnConfiguration['config']['foreign_table_field']);				
			} else {
				$columnMap->setTypeOfRelation(TX_EXTMVC_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY);
				$columnMap->setChildClassName($columnConfiguration['config']['foreign_class']);
				$columnMap->setChildTableName($columnConfiguration['config']['foreign_table']);
				$columnMap->setChildTableWhere($columnConfiguration['config']['foreign_table_where']);
				$columnMap->setChildSortbyFieldName($columnConfiguration['config']['foreign_sortby']);
				$columnMap->setParentKeyFieldName($columnConfiguration['config']['foreign_field']);
				$columnMap->setParentTableFieldName($columnConfiguration['config']['foreign_table_field']);
			}
		} elseif (array_key_exists('MM', $columnConfiguration['config'])) {
			$columnMap->setTypeOfRelation(TX_EXTMVC_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY);
			$columnMap->setChildClassName($columnConfiguration['config']['foreign_class']);
			$columnMap->setChildTableName($columnConfiguration['config']['foreign_table']);
			$columnMap->setRelationTableName($columnConfiguration['config']['MM']);
		} else {
			$columnMap->setTypeOfRelation(TX_EXTMVC_Persistence_Mapper_ColumnMap::RELATION_NONE);
		}
	}

	public function setColumnMaps(array $columnMaps) {
		$this->columnMaps = $columnMaps;
	}

	public function addColumnMap(TX_EXTMVC_Persistence_Mapper_ColumnMap $columnMap) {
		$this->columnMaps[$columnMap->getPropertyName()] = $columnMap;
	}

	public function addColumn($columnName, $typeOfValue = TX_EXTMVC_Persistence_Mapper_ColumnMap::TYPE_STRING, $typeOfRelation = TX_EXTMVC_Persistence_Mapper_ColumnMap::RELATION_NONE) {
		$columnMap = new TX_EXTMVC_Persistence_Mapper_ColumnMap($columnName);
		$columnMap->setTypeOfValue($typeOfValue);
		$columnMap->setTypeOfRelation($typeOfRelation);
		$this->addColumnMap($columnMap);
		return $this;
	}

	public function getColumnMaps() {
		return $this->columnMaps;
	}

	public function getColumnMap($propertyName) {
		return $this->columnMaps[$propertyName];
	}

	public function getColumnList() {
		$columnList = '';
		foreach ($this->columnMaps as $columnMap) {
			if ($columnList !== '') {
				$columnList .= ',';
			}
			$columnList .= $columnMap->getColumnName();
		}
		return $columnList;
	}

	/**
	 * Returns TRUE if the property is persistable (configured in $TCA)
	 *
	 * @param string $propertyName The property name
	 * @return boolean TRUE if the property is persistable (configured in $TCA)
	 */
	public function isPersistableProperty($propertyName) {
		return isset($this->columnMaps[$propertyName]);
	}

	/**
	 * Returns the name of a column indicating the 'deleted' state of the row
	 *
	 * @return string The class name
	 */
	public function getDeletedColumnName() {
		return $GLOBALS['TCA'][$this->getTableName()]['ctrl']['delete'];
	}

	/**
	 * Returns the name of a column indicating the 'hidden' state of the row
	 *
	 */
	public function getHiddenColumnName() {;
		return $GLOBALS['TCA'][$this->getTableName()]['ctrl']['enablecolumns']['disabled'];
	}

	/**
	 * Converts a value from a database field type to a property type
	 *
	 * @param string $className The class name
	 * @param string $propertyName The property name
	 * @param mixed $fieldValue The field value
	 * @return mixed The converted value
	 */
	// TODO convertion has to be revised
	public function convertFieldValueToPropertyValue($propertyName, $fieldValue) {
		$columnMap = $this->getColumnMap($propertyName);
		if ($columnMap->getTypeOfValue() === TX_EXTMVC_Persistence_Mapper_ColumnMap::TYPE_DATE) {
			$convertedValue = new DateTime(strftime('%Y-%m-%d %H:%M', $fieldValue));
		} elseif ($columnMap->getTypeOfValue() === TX_EXTMVC_Persistence_Mapper_ColumnMap::TYPE_BOOLEAN) {
			if ($fieldValue === '0') {
				$convertedValue = FALSE;
			} else {
				$convertedValue = TRUE;
			}
		} else {
			$convertedValue = $fieldValue;
		}
		return $convertedValue;
	}

	/**
	 * Converts a value from a property type to a database field type
	 *
	 * @param mixed $propertyValue The property value
	 * @return mixed The converted value
	 */
	public function convertPropertyValueToFieldValue($propertyValue) {
		if ($propertyValue instanceof DateTime) {
			$convertedValue = $propertyValue->format('U');
		} elseif (is_bool($propertyValue)) {
			$convertedValue = $propertyValue ? 1 : 0;
		} else {
			$convertedValue = $propertyValue;
		}
		return $convertedValue;
	}

}
?>