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
 * A factory for a data map to map a single table configured in $TCA on a domain object.
 *
 * @package Extbase
 * @subpackage Persistence\Mapper
 * @version $ID:$
 */
class Tx_Extbase_Persistence_Mapper_DataMapFactory {
	
	/**
	 * @var Tx_Extbase_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * Injects the reflection service
	 *
	 * @param Tx_Extbase_Reflection_Service $reflectionService
	 * @return void
	 */
	public function injectReflectionService(Tx_Extbase_Reflection_Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}
	
	/**
	 * Builds a data map by adding column maps for all the configured columns in the $TCA.
	 * It also resolves the type of values the column is holding and the typo of relation the column
	 * represents.
	 *
	 * @param string $className The class name you want to fetch the Data Map for
	 * @return Tx_Extbase_Persistence_Mapper_DataMap The data map
	 */
	public function buildDataMap($className) {
		if (!class_exists($className)) {
			throw new Tx_Extbase_Persistence_Exception_InvalidClass('Could not find class definition for name "' . $className . '". This could be caused by a mis-spelling of the class name in the class definition.');
		}

		$recordType = NULL;
		$subclasses = array();
		$tableName = strtolower($className);
		$columnMapping = array();
		
		$extbaseFrameworkConfiguration = Tx_Extbase_Dispatcher::getExtbaseFrameworkConfiguration();
		$classSettings = $extbaseFrameworkConfiguration['persistence']['classes'][$className];
		if ($classSettings !== NULL) {
			if (isset($classSettings['subclasses']) && is_array($classSettings['subclasses'])) {
				$subclasses = $classSettings['subclasses'];
			}
			if (isset($classSettings['mapping']['recordType']) && strlen($classSettings['mapping']['recordType']) > 0) {
				$recordType = $classSettings['mapping']['recordType'];
			}
			if (isset($classSettings['mapping']['tableName']) && strlen($classSettings['mapping']['tableName']) > 0) {
				$tableName = $classSettings['mapping']['tableName'];
			}
			$classHierachy = array_merge(array($className), class_parents($className));
			foreach ($classHierachy as $currentClassName) {
				if (in_array($currentClassName, array('Tx_Extbase_DomainObject_AbstractEntity', 'Tx_Extbase_DomainObject_AbstractValueObject'))) {
					break;
				}
				$currentTableName = strtolower($currentClassName);
				$currentClassSettings = $extbaseFrameworkConfiguration['persistence']['classes'][$currentClassName];
				if ($currentClassSettings !== NULL) {
					if (isset($currentClassSettings['mapping']['columns']) && is_array($currentClassSettings['mapping']['columns'])) {
						$columnMapping = t3lib_div::array_merge_recursive_overrule($columnMapping, $currentClassSettings['mapping']['columns'], 0, FALSE); // FALSE means: do not include empty values form 2nd array
					}
				}
			}
		}

		$dataMap = t3lib_div::makeInstance('Tx_Extbase_Persistence_Mapper_DataMap', $className, $tableName, $recordType, $subclasses);
		$dataMap = $this->addMetaDataColumnNames($dataMap, $tableName);

		// $classPropertyNames = $this->reflectionService->getClassPropertyNames($className);
		$tcaColumnsDefinition = $this->getColumnsDefinition($tableName);
		$tcaColumnsDefinition = t3lib_div::array_merge_recursive_overrule($tcaColumnsDefinition, $columnMapping); // TODO Is this is too powerful?
		foreach ($tcaColumnsDefinition as $columnName => $columnDefinition) {
			if (isset($columnDefinition['mapOnProperty'])) {
				$propertyName = $columnDefinition['mapOnProperty'];
			} else {
				$propertyName = Tx_Extbase_Utility_Extension::convertUnderscoredToLowerCamelCase($columnName);
			}
			// if (in_array($propertyName, $classPropertyNames)) { // TODO Enable check for property existance
				$columnMap = new Tx_Extbase_Persistence_Mapper_ColumnMap($columnName, $propertyName);
				$propertyMetaData = $this->reflectionService->getClassSchema($className)->getProperty($propertyName);
				$columnMap = $this->setRelations($columnMap, $columnDefinition['config'], $propertyMetaData);
				$dataMap->addColumnMap($columnMap);
			// }
		}
		// debug($dataMap);
		return $dataMap;
	}
		
	/**
	 * Returns the TCA ctrl section of the specified table; or NULL if not set
	 *
	 * @param string $tableName An optional table name to fetch the columns definition from
	 * @return array The TCA columns definition
	 */
	protected function getControlSection($tableName) {
		$this->includeTca($tableName);
		return is_array($GLOBALS['TCA'][$tableName]['ctrl']) ? $GLOBALS['TCA'][$tableName]['ctrl'] : NULL;
	}
	
	/**
	 * Returns the TCA columns array of the specified table
	 *
	 * @param string $tableName An optional table name to fetch the columns definition from
	 * @return array The TCA columns definition
	 */
	protected function getColumnsDefinition($tableName) {
		$this->includeTca($tableName);
		return is_array($GLOBALS['TCA'][$tableName]['columns']) ? $GLOBALS['TCA'][$tableName]['columns'] : array();
	}
	
	/**
	 * Includes the TCA for the given table
	 *
	 * @param string $tableName An optional table name to fetch the columns definition from
	 * @return void
	 */
	protected function includeTca($tableName) {
		if (TYPO3_MODE === 'FE') {
			$GLOBALS['TSFE']->includeTCA();
		}
		t3lib_div::loadTCA($tableName);
	}
	
	protected function addMetaDataColumnNames(Tx_Extbase_Persistence_Mapper_DataMap $dataMap, $tableName) {
		$controlSection = $GLOBALS['TCA'][$tableName]['ctrl'];
		$dataMap->setPageIdColumnName('pid');
		if (isset($controlSection['tstamp'])) $dataMap->setModificationDateColumnName($controlSection['tstamp']);
		if (isset($controlSection['crdate'])) $dataMap->setCreationDateColumnName($controlSection['crdate']);
		if (isset($controlSection['cruser_id'])) $dataMap->setCreatorColumnName($controlSection['cruser_id']);
		if (isset($controlSection['delete'])) $dataMap->setDeletedFlagColumnName($controlSection['delete']);
		if (isset($controlSection['languageField'])) $dataMap->setLanguageIdColumnName($controlSection['languageField']);
		if (isset($controlSection['transOrigPointerField'])) $dataMap->setTranslationOriginColumnName($controlSection['transOrigPointerField']);
		if (isset($controlSection['type'])) $dataMap->setRecordTypeColumnName($controlSection['type']);
		if (isset($controlSection['enablecolumns']['disabled'])) $dataMap->setDisabledFlagColumnName($controlSection['enablecolumns']['disabled']);
		if (isset($controlSection['enablecolumns']['starttime'])) $dataMap->setStartTimeColumnName($controlSection['enablecolumns']['starttime']);
		if (isset($controlSection['enablecolumns']['endtime'])) $dataMap->setEndTimeColumnName($controlSection['enablecolumns']['endtime']);
		if (isset($controlSection['enablecolumns']['fe_group'])) $dataMap->setFrontEndUserGroupColumnName($controlSection['enablecolumns']['fe_group']);
		return $dataMap;
	}
		
	/**
	 * This method tries to determine the type of type of relation to other tables and sets it based on
	 * the $TCA column configuration
	 *
	 * @param Tx_Extbase_Persistence_Mapper_ColumnMap $columnMap The column map
	 * @param string $columnConfiguration The column configuration from $TCA
	 * @param array $propertyMetaData The property metadata as delivered by the reflection service
	 * @return void
	 */
	protected function setRelations(Tx_Extbase_Persistence_Mapper_ColumnMap $columnMap, $columnConfiguration, $propertyMetaData) {
		if (isset($columnConfiguration)) {
			if (isset($columnConfiguration['MM']) || isset($columnConfiguration['foreign_selector'])) {
				$columnMap = $this->setManyToManyRelation($columnMap, $columnConfiguration);
			} elseif (isset($propertyMetaData['elementType'])) {
				$columnMap = $this->setOneToManyRelation($columnMap, $columnConfiguration);
			} elseif (isset($propertyMetaData['type']) && strpos($propertyMetaData['type'], '_') !== FALSE) {
				$columnMap = $this->setOneToOneRelation($columnMap, $columnConfiguration);
			} else {
					$columnMap->setTypeOfRelation(Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_NONE);
			}
		} else {
			$columnMap->setTypeOfRelation(Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_NONE);
		}
		return $columnMap;
	}
	
	/**
	 * This method sets the configuration for a 1:1 relation based on
	 * the $TCA column configuration
	 *
	 * @param string $columnMap The column map
	 * @param string $columnConfiguration The column configuration from $TCA
	 * @return void
	 */
	protected function setOneToOneRelation(Tx_Extbase_Persistence_Mapper_ColumnMap $columnMap, $columnConfiguration) {
		$columnMap->setTypeOfRelation(Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_ONE);
		$columnMap->setChildTableName($columnConfiguration['foreign_table']);
		$columnMap->setChildTableWhereStatement($columnConfiguration['foreign_table_where']);
		$columnMap->setChildSortbyFieldName($columnConfiguration['foreign_sortby']);
		$columnMap->setParentKeyFieldName($columnConfiguration['foreign_field']);
		$columnMap->setParentTableFieldName($columnConfiguration['foreign_table_field']);
		return $columnMap;
	}
	
	/**
	 * This method sets the configuration for a 1:n relation based on
	 * the $TCA column configuration
	 *
	 * @param string $columnMap The column map
	 * @param string $columnConfiguration The column configuration from $TCA
	 * @return void
	 */
	protected function setOneToManyRelation(Tx_Extbase_Persistence_Mapper_ColumnMap $columnMap, $columnConfiguration) {
		$columnMap->setTypeOfRelation(Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY);
		$columnMap->setChildTableName($columnConfiguration['foreign_table']);
		$columnMap->setChildTableWhereStatement($columnConfiguration['foreign_table_where']);
		$columnMap->setChildSortbyFieldName($columnConfiguration['foreign_sortby']);
		$columnMap->setParentKeyFieldName($columnConfiguration['foreign_field']);
		$columnMap->setParentTableFieldName($columnConfiguration['foreign_table_field']);
		return $columnMap;
	}
	
	/**
	 * This method sets the configuration for a m:n relation based on
	 * the $TCA column configuration
	 *
	 * @param string $columnMap The column map
	 * @param string $columnConfiguration The column configuration from $TCA
	 * @return void
	 */
	protected function setManyToManyRelation(Tx_Extbase_Persistence_Mapper_ColumnMap $columnMap, $columnConfiguration) {
		$columnMap->setTypeOfRelation(Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY);
		if (isset($columnConfiguration['MM'])) {
			$columnMap->setChildTableName($columnConfiguration['foreign_table']);
			$columnMap->setChildTableWhereStatement($columnConfiguration['foreign_table_where']);
			$columnMap->setRelationTableName($columnConfiguration['MM']);
			if (is_array($columnConfiguration['MM_match_fields'])) {
				$columnMap->setRelationTableMatchFields($columnConfiguration['MM_match_fields']);
			}
			if (is_array($columnConfiguration['MM_insert_fields'])) {
				$columnMap->setRelationTableInsertFields($columnConfiguration['MM_insert_fields']);
			}
			$columnMap->setRelationTableWhereStatement($columnConfiguration['MM_table_where']);
			if (!empty($columnConfiguration['MM_opposite_field'])) {
				$columnMap->setParentKeyFieldName('uid_foreign');
				$columnMap->setChildKeyFieldName('uid_local');
				$columnMap->setChildSortByFieldName('sorting_foreign');
			} else {
				$columnMap->setParentKeyFieldName('uid_local');
				$columnMap->setChildKeyFieldName('uid_foreign');
				$columnMap->setChildSortByFieldName('sorting');
			}
		} elseif (isset($columnConfiguration['foreign_selector'])) {
			$columns = $this->getColumnsDefinition($columnConfiguration['foreign_table']);
			$childKeyFieldName = $columnConfiguration['foreign_selector'];
			$columnMap->setChildTableName($columns[$childKeyFieldName]['config']['foreign_table']);
			$columnMap->setRelationTableName($columnConfiguration['foreign_table']);
			$columnMap->setParentKeyFieldName($columnConfiguration['foreign_field']);
			$columnMap->setChildKeyFieldName($childKeyFieldName);
			$columnMap->setChildSortByFieldName($columnConfiguration['foreign_sortby']);
		} else {
			throw new Tx_Extbase_Persistence_Exception_UnsupportedRelation('The given information to build a many-to-many-relation was not sufficient. Check your TCA definitions. mm-relations with IRRE must have at least a defined "MM" or "foreign_selector".', 1268817963);
		}
		if ($this->getControlSection($columnMap->getRelationTableName()) !== NULL) {
			$columnMap->setRelationTablePageIdColumnName('pid');
		}
		return $columnMap;
	}
		
}