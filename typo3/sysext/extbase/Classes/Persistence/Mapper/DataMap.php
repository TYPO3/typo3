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
	public function __construct($className) {
		$this->setClassName($className);
		$this->setTableName($this->determineTableName($className));
		t3lib_div::loadTCA($this->getTableName());
		$this->initialize();
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
	 * Returns the table name for a given class name. If there is an alias defined in the $TCA, it takes the alias name.
	 * Otherwise it converts the class anme to lowercase by default.
	 *
	 * @package default
	 */
	protected function determineTableName($className) {
		// TODO Implement table name aliases
		return strtolower($className);
	}

	/**
	 * Initializes the data map by adding column maps for all the configured columns in the $TCA. 
	 * It also resolves the type of values the column is holding and the typo of relation the column 
	 * represents.
	 *
	 * @return void
	 */
	protected function initialize() {
		$columns = $GLOBALS['TCA'][$this->getTableName()]['columns'];
		$this->addCommonColumns();
		if (is_array($columns)) {
			foreach ($columns as $columnName => $columnConfiguration) {
				// TODO convert underscore column names to lowercamelcase
				$columnMap = new Tx_Extbase_Persistence_Mapper_ColumnMap($columnName, $this);
				$this->setTypeOfValue($columnMap, $columnConfiguration);
				// TODO support for IRRE
				// TODO support for MM_insert_fields and MM_match_fields
				// SK: Discuss the above things
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
		$this->addColumn('uid', Tx_Extbase_Persistence_Mapper_ColumnMap::TYPE_INTEGER);
		$this->addColumn('pid', Tx_Extbase_Persistence_Mapper_ColumnMap::TYPE_INTEGER);
		if ($this->hasTimestampColumn()) {
			$this->addColumn($this->getTimestampColumnName(), Tx_Extbase_Persistence_Mapper_ColumnMap::TYPE_DATE);
		}
		if ($this->hasCreationDateColumn()) {
			$this->addColumn($this->getCreationDateColumnName(), Tx_Extbase_Persistence_Mapper_ColumnMap::TYPE_DATE);
		}
		if ($this->hasCreatorUidColumn()) {
			$this->addColumn($this->getCreatorUidColumnName(), Tx_Extbase_Persistence_Mapper_ColumnMap::TYPE_INTEGER);
		}
		if ($this->hasDeletedColumn()) {
			$this->addColumn($this->getDeletedColumnName(), Tx_Extbase_Persistence_Mapper_ColumnMap::TYPE_BOOLEAN);
		}
		if ($this->hasHiddenColumn()) {
			$this->addColumn($this->getHiddenColumnName(), Tx_Extbase_Persistence_Mapper_ColumnMap::TYPE_BOOLEAN);
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
	protected function setTypeOfValue(Tx_Extbase_Persistence_Mapper_ColumnMap &$columnMap, $columnConfiguration) {
		$evalConfiguration = t3lib_div::trimExplode(',', $columnConfiguration['config']['eval']);
		if (in_array('date', $evalConfiguration) || in_array('datetime', $evalConfiguration)) {
			$columnMap->setTypeOfValue(Tx_Extbase_Persistence_Mapper_ColumnMap::TYPE_DATE);
		} elseif ($columnConfiguration['config']['type'] === 'check' && empty($columnConfiguration['config']['items'])) {
			$columnMap->setTypeOfValue(Tx_Extbase_Persistence_Mapper_ColumnMap::TYPE_BOOLEAN);
		} elseif (in_array('int', $evalConfiguration)) {
			$columnMap->setTypeOfValue(Tx_Extbase_Persistence_Mapper_ColumnMap::TYPE_INTEGER);
		} elseif (in_array('double2', $evalConfiguration)) {
			$columnMap->setTypeOfValue(Tx_Extbase_Persistence_Mapper_ColumnMap::TYPE_FLOAT);
		} else {
			$columnMap->setTypeOfValue(Tx_Extbase_Persistence_Mapper_ColumnMap::TYPE_STRING);
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
	 * @param string $typeOfValue The type of value (default: string)
	 * @param string $typeOfRelation The type of relation (default: none)
	 * @return Tx_Extbase_Persistence_Mapper_DataMap Returns itself for a fluent interface
	 */
	public function addColumn($columnName, $typeOfValue = Tx_Extbase_Persistence_Mapper_ColumnMap::TYPE_STRING, $typeOfRelation = Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_NONE) {
		$columnMap = new Tx_Extbase_Persistence_Mapper_ColumnMap($columnName);
		$columnMap->setTypeOfValue($typeOfValue);
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
	 * Converts a value from a database field type to a property type
	 *
	 * @param string $className The class name
	 * @param string $propertyName The property name
	 * @param mixed $fieldValue The field value
	 * @return mixed The converted value
	 */
	public function convertFieldValueToPropertyValue($propertyName, $fieldValue) {
		$columnMap = $this->getColumnMap($propertyName);
		if ($columnMap->getTypeOfValue() === Tx_Extbase_Persistence_Mapper_ColumnMap::TYPE_DATE) {
			$convertedValue = new DateTime(strftime('%Y-%m-%d %H:%M:%S', $fieldValue));
		} elseif ($columnMap->getTypeOfValue() === Tx_Extbase_Persistence_Mapper_ColumnMap::TYPE_BOOLEAN) {
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
	 * @param boolean $fullQuoteString TRUE if a field value of type string should be full quoted via $GLOBALS['TYPO3_DB']->fullQuoteStr()
	 * @return mixed The converted value
	 */
	public function convertPropertyValueToFieldValue($propertyValue, $fullQuoteString = TRUE) {
		if (is_bool($propertyValue)) {
			$convertedValue = $propertyValue ? 1 : 0;
		} elseif ($propertyValue instanceof Tx_Extbase_DomainObject_AbstractDomainObject) {
			$convertedValue = $propertyValue->getUid();
		} elseif ($propertyValue instanceof DateTime) {
			$convertedValue = $propertyValue->format('U');
		} elseif (is_int($propertyValue)) {
			$convertedValue = $propertyValue;
		} else {
			$convertedValue = $fullQuoteString === TRUE ? $GLOBALS['TYPO3_DB']->fullQuoteStr((string)$propertyValue, '') : $propertyValue;
		}
		return $convertedValue;
	}

}