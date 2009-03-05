<?php

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Utility/TX_EXTMVC_Utility_Strings.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/Mapper/TX_EXTMVC_Persistence_Mapper_ColumnMap.php');

/**
 * A data map to map a single table configured in $TCA on a domain object.
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Persistence_Mapper_DataMap {

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
	
	public function __construct($className) {
		$this->setClassName($className);
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
	
	public function initialize() {
		$columns = $GLOBALS['TCA'][$this->getTableName()]['columns'];
		if (is_array($columns)) {
			$this->addCommonColumns();			
			$columnMapClassName = t3lib_div::makeInstanceClassName('TX_EXTMVC_Persistence_Mapper_ColumnMap');			
			foreach ($columns as $columnName => $columnConfiguration) {
				$columnMap = new $columnMapClassName($columnName, $this);
				$this->setTypeOfValue($columnMap, $columnConfiguration);
				// TODO support for IRRE
				// TODO support for MM_insert_fields and MM_match_fields
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
		$this->addColumn('cruser_id', TX_EXTMVC_Persistence_Mapper_ColumnMap::TYPE_DATE);
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
		if (array_key_exists('foreign_table', $columnConfiguration['config']) && !array_key_exists('MM', $columnConfiguration['config'])) {
			$columnMap->setTypeOfRelation(TX_EXTMVC_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY);
			$columnMap->setChildClassName($columnConfiguration['config']['foreign_class']);
			$columnMap->setChildTableName($columnConfiguration['config']['foreign_table']);
			$columnMap->setChildTableWhere($columnConfiguration['config']['foreign_table_where']);
			$columnMap->setChildSortbyFieldName($columnConfiguration['config']['foreign_sortby']);
			$columnMap->setParentKeyFieldName($columnConfiguration['config']['foreign_field']);
			$columnMap->setParentTableFieldName($columnConfiguration['config']['foreign_table_field']);					
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
		$columnMapClassName = t3lib_div::makeInstanceClassName('TX_EXTMVC_Persistence_Mapper_ColumnMap');
		$columnMap = new $columnMapClassName($columnName);
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
		if (array_key_exists($propertyName, $this->columnMaps)) return TRUE;
		return FALSE;
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