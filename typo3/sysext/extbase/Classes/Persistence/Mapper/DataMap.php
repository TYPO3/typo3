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
 * @package Extbase
 * @subpackage extbase
 * @version $ID:$
 */
class Tx_Extbase_Persistence_Mapper_DataMap {
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
	// TODO Refactor to factory pattern (DataMapFactory) and value object (DataMap)  
	public function __construct($className, $tableName = '', array $mapping = array()) {
		$this->setClassName($className);
		if (empty($tableName)) {
			$this->setTableName(strtolower($className));
		} else {
			$this->setTableName($tableName);
		}
		$this->initialize($mapping);
	}

	/**
	 * Sets the name of the class the colum map represents
	 *
	 * @return void
	 */
	public function setClassName($className) {
		$this->className = $className;
	}

	/**
	 * Returns the name of the class the column map represents
	 *
	 * @return string The class name
	 */
	public function getClassName() {
		return $this->className;
	}

	/**
	 * Sets the name of the table the colum map represents
	 *
	 * @return void
	 */
	public function setTableName($tableName) {
		$this->tableName = $tableName;
	}

	/**
	 * Returns the name of the table the column map represents
	 *
	 * @return string The table name
	 */
	public function getTableName() {
		return $this->tableName;
	}
	
	/**
	 * Initializes the data map by adding column maps for all the configured columns in the $TCA. 
	 * It also resolves the type of values the column is holding and the typo of relation the column 
	 * represents.
	 *
	 * @return void
	 */
	protected function initialize(array $mapping) {
		t3lib_div::loadTCA($this->getTableName());
		$columns = $GLOBALS['TCA'][$this->getTableName()]['columns'];
		$this->addCommonColumns();
		if (is_array($columns)) {
			foreach ($columns as $columnName => $columnConfiguration) {
				if (!empty($mapping[$columnName]['mapOnProperty'])) {
					$propertyName = $mapping[$columnName]['mapOnProperty'];
				} else {
					$propertyName = t3lib_div::underscoredToLowerCamelCase($columnName);
				}
				$columnMap = new Tx_Extbase_Persistence_Mapper_ColumnMap($columnName, $propertyName);
				$this->setPropertyType($columnMap, $columnConfiguration);
				// TODO Check support for IRRE
				// TODO support for MM_insert_fields and MM_match_fields
				$this->setRelations($columnMap, $columnConfiguration);
				$this->addColumnMap($columnMap);
			}
		}
	}
	
	/**
	 * Adds available common columns (e.g. tstamp or crdate) to the data map. It takes the configured column names
	 * into account.
	 *
	 * @return void
	 */
	protected function addCommonColumns() {
		// TODO Decide whether we should add pid and uid columns by default
		$this->addColumn('uid', NULL, Tx_Extbase_Persistence_PropertyType::LONG);
		if ($this->hasPidColumn()) {
			$this->addColumn('pid', NULL, Tx_Extbase_Persistence_PropertyType::LONG);
		}
		if ($this->hasTimestampColumn()) {
			$this->addColumn($this->getTimestampColumnName(), NULL, Tx_Extbase_Persistence_PropertyType::DATE);
		}
		if ($this->hasCreationDateColumn()) {
			$this->addColumn($this->getCreationDateColumnName(), NULL, Tx_Extbase_Persistence_PropertyType::DATE);
		}
		if ($this->hasCreatorUidColumn()) {
			$this->addColumn($this->getCreatorUidColumnName(), NULL, Tx_Extbase_Persistence_PropertyType::LONG);
		}
		if ($this->hasDeletedColumn()) {
			$this->addColumn($this->getDeletedColumnName(), NULL, Tx_Extbase_Persistence_PropertyType::BOOLEAN);
		}
		if ($this->hasHiddenColumn()) {
			$this->addColumn($this->getHiddenColumnName(), NULL, Tx_Extbase_Persistence_PropertyType::BOOLEAN);
		}
	}

	/**
	 * This method tries to determine the type of value the column hold by inspectiong the $TCA column configuration
	 * and sets it.
	 *
	 * @param string $columnMap The column map
	 * @param string $columnConfiguration The column configuration from $TCA
	 * @return void
	 */
	protected function setPropertyType(Tx_Extbase_Persistence_Mapper_ColumnMap &$columnMap, $columnConfiguration) {
		$evalConfiguration = t3lib_div::trimExplode(',', $columnConfiguration['config']['eval']);
		if (in_array('date', $evalConfiguration) || in_array('datetime', $evalConfiguration)) {
			$columnMap->setPropertyType(Tx_Extbase_Persistence_PropertyType::DATE);
		} elseif ($columnConfiguration['config']['type'] === 'check' && empty($columnConfiguration['config']['items'])) {
			$columnMap->setPropertyType(Tx_Extbase_Persistence_PropertyType::BOOLEAN);
		} elseif (in_array('int', $evalConfiguration)) {
			$columnMap->setPropertyType(Tx_Extbase_Persistence_PropertyType::LONG);
		} elseif (in_array('double2', $evalConfiguration)) {
			$columnMap->setPropertyType(Tx_Extbase_Persistence_PropertyType::DOUBLE);
		} else {
			if (isset($columnConfiguration['config']['foreign_table']) && isset($columnConfiguration['config']['foreign_class'])) {
				if ($columnConfiguration['config']['loadingStrategy'] === 'proxy') {
					$columnMap->setLoadingStrategy(Tx_Extbase_Persistence_Mapper_ColumnMap::STRATEGY_PROXY);
				} else {
					$columnMap->setLoadingStrategy(Tx_Extbase_Persistence_Mapper_ColumnMap::STRATEGY_EAGER);
				}
				$columnMap->setPropertyType(Tx_Extbase_Persistence_PropertyType::REFERENCE);
			} else {
				$columnMap->setPropertyType(Tx_Extbase_Persistence_PropertyType::STRING);
			}
		}
	}

	/**
	 * This method tries to determine the type of type of relation to other tables and sets it based on 
	 * the $TCA column configuration
	 *
	 * @param string $columnMap The column map
	 * @param string $columnConfiguration The column configuration from $TCA
	 * @return void
	 */
	protected function setRelations(Tx_Extbase_Persistence_Mapper_ColumnMap &$columnMap, $columnConfiguration) {
		if (isset($columnConfiguration['config']['foreign_table']) && !isset($columnConfiguration['config']['MM'])) {
			if ($columnConfiguration['config']['maxitems'] == 1) {
				$columnMap->setTypeOfRelation(Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_ONE);
				$columnMap->setChildClassName($columnConfiguration['config']['foreign_class']);
				$columnMap->setChildTableName($columnConfiguration['config']['foreign_table']);
				$columnMap->setChildTableWhere($columnConfiguration['config']['foreign_table_where']);
				$columnMap->setChildSortbyFieldName($columnConfiguration['config']['foreign_sortby']);
				$columnMap->setParentKeyFieldName($columnConfiguration['config']['foreign_field']);
				$columnMap->setParentTableFieldName($columnConfiguration['config']['foreign_table_field']);
			} else {
				$columnMap->setTypeOfRelation(Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY);
				$columnMap->setChildClassName($columnConfiguration['config']['foreign_class']);
				$columnMap->setChildTableName($columnConfiguration['config']['foreign_table']);
				$columnMap->setChildTableWhere($columnConfiguration['config']['foreign_table_where']);
				$columnMap->setChildSortbyFieldName($columnConfiguration['config']['foreign_sortby']);
				$columnMap->setParentKeyFieldName($columnConfiguration['config']['foreign_field']);
				$columnMap->setParentTableFieldName($columnConfiguration['config']['foreign_table_field']);
			}
			//			TODO Support MM_match_fields
		} elseif (array_key_exists('MM', $columnConfiguration['config'])) {
			$columnMap->setTypeOfRelation(Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY);
			$columnMap->setChildClassName($columnConfiguration['config']['foreign_class']);
			$columnMap->setChildTableName($columnConfiguration['config']['foreign_table']);
			$columnMap->setRelationTableName($columnConfiguration['config']['MM']);
		} else {
			$columnMap->setTypeOfRelation(Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_NONE);
		}
	}

	/**
	 * Sets the column maps.
	 *
	 * @param array $columnMaps The column maps stored in a flat array.
	 * @return void
	 */
	public function setColumnMaps(array $columnMaps) {
		$this->columnMaps = $columnMaps;
	}

	/**
	 * Adds a given column map to the data map.
	 *
	 * @param Tx_Extbase_Persistence_Mapper_ColumnMap $columnMap The column map 
	 * @return void
	 */
	public function addColumnMap(Tx_Extbase_Persistence_Mapper_ColumnMap $columnMap) {
		$this->columnMaps[$columnMap->getPropertyName()] = $columnMap;
	}

	/**
	 * Builds a column map out of the given column name, type of value (optional), and type of 
	 * relation (optional) and adds it to the data map.
	 *
	 * @param string $columnName The column name
	 * @param string $propertyName The property name
	 * @param string $propertyType The type of value (default: string)
	 * @param string $typeOfRelation The type of relation (default: none)
	 * @return Tx_Extbase_Persistence_Mapper_DataMap Returns itself for a fluent interface
	 */
	public function addColumn($columnName, $propertyName = '', $propertyType = Tx_Extbase_Persistence_PropertyType::STRING, $typeOfRelation = Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_NONE) {
		if (empty($propertyName)) {
			$propertyName = t3lib_div::underscoredToLowerCamelCase($columnName);
		}
		
		$columnMap = new Tx_Extbase_Persistence_Mapper_ColumnMap($columnName, $propertyName);
		$columnMap->setPropertyType($propertyType);
		$columnMap->setTypeOfRelation($typeOfRelation);
		$this->addColumnMap($columnMap);
		return $this;
	}

	/**
	 * Returns all column maps
	 *
	 * @return array The column maps
	 */
	public function getColumnMaps() {
		return $this->columnMaps;
	}

	/**
	 * Returns the column map corresponding to the given property name.
	 *
	 * @param string $propertyName 
	 * @return Tx_Extbase_Persistence_Mapper_ColumnMap|NULL The column map or NULL if no corresponding column map was found.
	 */
	public function getColumnMap($propertyName) {
		return $this->columnMaps[$propertyName];
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
	 * Check if versioning is enabled .
	 *
	 * @return boolean
	 */
	public function isVersionable() {
		return ($GLOBALS['TCA'] [$this->tableName] ['ctrl'] ['versioningWS'] === '1');
	}

	/**
	 * Returns TRUE if the table has a pid column holding the id of the page the record is virtually stored on.
	 * Currently we don't support tables without a pid column.
	 *
	 * @return boolean The result
	 */
	public function hasPidColumn() {
		// TODO Should we implement a check for having a pid column?
		return TRUE;
	}

	/**
	 * Returns the name of a column holding the timestamp the record was modified
	 *
	 * @return string The field name
	 */
	public function getTimestampColumnName() {
		return $GLOBALS['TCA'][$this->getTableName()]['ctrl']['tstamp'];
	}

	/**
	 * Returns TRUE if the table has a column holding the timestamp the record was modified
	 *
	 * @return boolean The result
	 */
	public function hasTimestampColumn() {
		return !empty($GLOBALS['TCA'][$this->getTableName()]['ctrl']['tstamp']);
	}

	/**
	 * Returns the name of a column holding the creation date timestamp
	 *
	 * @return string The field name
	 */
	public function getCreationDateColumnName() {
		return $GLOBALS['TCA'][$this->getTableName()]['ctrl']['crdate'];
	}

	/**
	 * Returns TRUE if the table has olumn holding the creation date timestamp
	 *
	 * @return boolean The result
	 */
	public function hasCreationDateColumn() {
		return !empty($GLOBALS['TCA'][$this->getTableName()]['ctrl']['crdate']);
	}

	/**
	 * Returns the name of a column holding the uid of the back-end user who created this record
	 *
	 * @return string The field name
	 */
	public function getCreatorUidColumnName() {
		return $GLOBALS['TCA'][$this->getTableName()]['ctrl']['cruser_id'];
	}

	/**
	 * Returns TRUE if the table has a column holding the uid of the back-end user who created this record
	 *
	 * @return boolean The result
	 */
	public function hasCreatorUidColumn() {
		return !empty($GLOBALS['TCA'][$this->getTableName()]['ctrl']['cruser_id']);
	}

	/**
	 * Returns the name of a column indicating the 'deleted' state of the row
	 *
	 * @return string The field name
	 */
	public function getDeletedColumnName() {
		return $GLOBALS['TCA'][$this->getTableName()]['ctrl']['delete'];
	}

	/**
	 * Returns TRUE if the table has a column indicating the 'deleted' state of the row
	 *
	 * @return boolean The result
	 */
	public function hasDeletedColumn() {
		return !empty($GLOBALS['TCA'][$this->getTableName()]['ctrl']['delete']);
	}

	/**
	 * Returns the name of a column indicating the 'hidden' state of the row
	 *
	 * @return string The field name
	 */
	public function getHiddenColumnName() {
		return $GLOBALS['TCA'][$this->getTableName()]['ctrl']['enablecolumns']['disabled'];
	}

	/**
	 * Returns TRUE if the table has a column indicating the 'hidden' state of the row
	 *
	 * @return boolean The result
	 */
	public function hasHiddenColumn() {
		return !empty($GLOBALS['TCA'][$this->getTableName()]['ctrl']['enablecolumns']['disabled']);
	}

	/**
	 * Returns the name of a column holding the timestamp the record should not displayed before
	 *
	 * @return string The field name
	 */
	public function getStartTimeColumnName() {
		return $GLOBALS['TCA'][$this->getTableName()]['ctrl']['enablecolumns']['starttime'];
	}

	/**
	 * Returns TRUE if the table has a column holding the timestamp the record should not displayed before
	 *
	 * @return boolean The result
	 */
	public function hasStartTimeColumn() {
		return !empty($GLOBALS['TCA'][$this->getTableName()]['ctrl']['enablecolumns']['starttime']);
	}

	/**
	 * Returns the name of a column holding the timestamp the record should not displayed afterwards
	 *
	 * @return string The field name
	 */
	public function getEndTimeColumnName() {
		return $GLOBALS['TCA'][$this->getTableName()]['ctrl']['enablecolumns']['endtime'];
	}

	/**
	 * Returns TRUE if the table has a column holding the timestamp the record should not displayed afterwards
	 *
	 * @return boolean The result
	 */
	public function hasEndTimeColumn() {
		return !empty($GLOBALS['TCA'][$this->getTableName()]['ctrl']['enablecolumns']['endtime']);
	}

	/**
	 * Returns the name of a column holding the uid of the front-end user group which is allowed to edit this record
	 *
	 * @return string The field name
	 */
	public function getFrontEndUserGroupColumnName() {
		return $GLOBALS['TCA'][$this->getTableName()]['ctrl']['enablecolumns']['fe_group'];
	}

	/**
	 * Returns TRUE if the table has a column holding the uid of the front-end user group which is allowed to edit this record
	 *
	 * @return boolean The result
	 */
	public function hasFrontEndUserGroupColumn() {
		return !empty($GLOBALS['TCA'][$this->getTableName()]['ctrl']['enablecolumns']['fe_group']);
	}

	/**
	 * Converts a field name to the property name. It respects property name aliases defined in $TCA.
	 *
	 * @param string $fieldName The field name
	 * @return string $propertyName The property name
	 */
	public function convertFieldNameToPropertyName($fieldName) {
		$propertyName = $fieldName;
		return $propertyName; // TODO Implement aliases for field names (see also convertPropertyNameToFieldName())
	}

	/**
	 * Converts a preoperty name to the field name. It respects property name aliases defined in $TCA.
	 *
	 * @param string $fieldName The field name
	 * @return string $propertyName The property name
	 */
	public function convertPropertyNameToFieldName($propertyName) {
		$fieldName = $propertyName;
		return $fieldName;
	}

	/**
	 * Converts the given string into the given type
	 *
	 * @param integer $type one of the constants defined in Tx_Extbase_Persistence_PropertyType
	 * @param string $string a string representing a value of the given type
	 *
	 * @return string|int|float|DateTime|boolean
	 */
	public function convertFieldValueToPropertyValue($type, $string) {
		switch ($type) {
			case Tx_Extbase_Persistence_PropertyType::LONG:
				return (int) $string;
			case Tx_Extbase_Persistence_PropertyType::DOUBLE:
			case Tx_Extbase_Persistence_PropertyType::DECIMAL:
				return (float) $string;
			case Tx_Extbase_Persistence_PropertyType::DATE:
				return new DateTime(strftime('%Y-%m-%d %H:%M:%S', $string)); // TODO Check for Time Zone issues
			case Tx_Extbase_Persistence_PropertyType::BOOLEAN:
				return (boolean) $string;
			default:
				return $string;
		}
	}

	/**
	 * Converts a value from a property type to a database field type
	 *
	 * @param mixed $propertyValue The property value
	 * @return mixed The converted value
	 */
	public function convertPropertyValueToFieldValue($propertyValue) {
		if (is_bool($propertyValue)) {
			$convertedValue = $propertyValue ? 1 : 0;
		} elseif ($propertyValue instanceof Tx_Extbase_DomainObject_AbstractDomainObject) {
			$convertedValue = $propertyValue->getUid();
		} elseif (is_a($propertyValue, 'DateTime')) {
			$convertedValue = $propertyValue->format('U');
		} elseif (is_int($propertyValue)) {
			$convertedValue = $propertyValue;
		} else {
			$convertedValue = $propertyValue;
		}
		return $convertedValue;
	}

}