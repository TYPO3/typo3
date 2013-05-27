<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Mapper;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 * A factory for a data map to map a single table configured in $TCA on a domain object.
 */
class DataMapFactory implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Core\Cache\CacheManager
	 */
	protected $cacheManager;

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
	 */
	protected $dataMapCache;

	/**
	 * Injects the reflection service
	 *
	 * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \TYPO3\CMS\Core\Cache\CacheManager $cacheManager
	 */
	public function injectCacheManager(\TYPO3\CMS\Core\Cache\CacheManager $cacheManager) {
		$this->cacheManager = $cacheManager;
	}

	/**
	 * Lifecycle method
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->dataMapCache = $this->cacheManager->getCache('extbase_datamapfactory_datamap');
	}

	/**
	 * Builds a data map by adding column maps for all the configured columns in the $TCA.
	 * It also resolves the type of values the column is holding and the typo of relation the column
	 * represents.
	 *
	 * @param string $className The class name you want to fetch the Data Map for
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap The data map
	 */
	public function buildDataMap($className) {
		$dataMap = $this->dataMapCache->get(str_replace('\\', '%', $className));
		if ($dataMap === FALSE) {
			$dataMap = $this->buildDataMapInternal($className);
			$this->dataMapCache->set(str_replace('\\', '%', $className), $dataMap);
		}
		return $dataMap;
	}

	/**
	 * Builds a data map by adding column maps for all the configured columns in the $TCA.
	 * It also resolves the type of values the column is holding and the typo of relation the column
	 * represents.
	 *
	 * @param string $className The class name you want to fetch the Data Map for
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidClassException
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap The data map
	 */
	protected function buildDataMapInternal($className) {
		if (!class_exists($className)) {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidClassException('Could not find class definition for name "' . $className . '". This could be caused by a mis-spelling of the class name in the class definition.');
		}
		$recordType = NULL;
		$subclasses = array();
		$tableName = $this->resolveTableName($className);
		$columnMapping = array();
		$frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		$classSettings = $frameworkConfiguration['persistence']['classes'][$className];
		if ($classSettings !== NULL) {
			if (isset($classSettings['subclasses']) && is_array($classSettings['subclasses'])) {
				$subclasses = $this->resolveSubclassesRecursive($frameworkConfiguration['persistence']['classes'], $classSettings['subclasses']);
			}
			if (isset($classSettings['mapping']['recordType']) && strlen($classSettings['mapping']['recordType']) > 0) {
				$recordType = $classSettings['mapping']['recordType'];
			}
			if (isset($classSettings['mapping']['tableName']) && strlen($classSettings['mapping']['tableName']) > 0) {
				$tableName = $classSettings['mapping']['tableName'];
			}
			$classHierarchy = array_merge(array($className), class_parents($className));
			foreach ($classHierarchy as $currentClassName) {
				if (in_array($currentClassName, array('TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity', 'TYPO3\\CMS\\Extbase\\DomainObject\\AbstractValueObject'))) {
					break;
				}
				$currentClassSettings = $frameworkConfiguration['persistence']['classes'][$currentClassName];
				if ($currentClassSettings !== NULL) {
					if (isset($currentClassSettings['mapping']['columns']) && is_array($currentClassSettings['mapping']['columns'])) {
						$columnMapping = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($columnMapping, $currentClassSettings['mapping']['columns'], 0, FALSE);
					}
				}
			}
		}
		/** @var $dataMap \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap */
		$dataMap = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMap', $className, $tableName, $recordType, $subclasses);
		$dataMap = $this->addMetaDataColumnNames($dataMap, $tableName);
		// $classPropertyNames = $this->reflectionService->getClassPropertyNames($className);
		$tcaColumnsDefinition = $this->getColumnsDefinition($tableName);
		$tcaColumnsDefinition = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($tcaColumnsDefinition, $columnMapping);
		// TODO Is this is too powerful?

		foreach ($tcaColumnsDefinition as $columnName => $columnDefinition) {
			if (isset($columnDefinition['mapOnProperty'])) {
				$propertyName = $columnDefinition['mapOnProperty'];
			} else {
				$propertyName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToLowerCamelCase($columnName);
			}
			// if (in_array($propertyName, $classPropertyNames)) { // TODO Enable check for property existance
			$columnMap = $this->createColumnMap($columnName, $propertyName);
			$propertyMetaData = $this->reflectionService->getClassSchema($className)->getProperty($propertyName);
			$columnMap = $this->setRelations($columnMap, $columnDefinition['config'], $propertyMetaData);
			$columnMap = $this->setFieldEvaluations($columnMap, $columnDefinition['config']);
			$dataMap->addColumnMap($columnMap);
		}
		return $dataMap;
	}

	/**
	 * Resolve the table name for the given class name
	 *
	 * @param string $className
	 * @return string The table name
	 */
	protected function resolveTableName($className) {
		if (strpos($className, '\\') !== FALSE) {
			$classNameParts = explode('\\', $className, 6);
			// Skip vendor and product name for core classes
			if (strpos($className, 'TYPO3\\CMS\\') === 0) {
				$classPartsToSkip = 2;
			} else {
				$classPartsToSkip = 1;
			}
			$tableName = 'tx_' . strtolower(implode('_', array_slice($classNameParts, $classPartsToSkip)));
		} else {
			$tableName = strtolower($className);
		}
		return $tableName;
	}

	/**
	 * Resolves all subclasses for the given set of (sub-)classes.
	 * The whole classes configuration is used to determine all subclasses recursively.
	 *
	 * @param array $classesConfiguration The framework configuration part [persistence][classes].
	 * @param array $subclasses An array of subclasses defined via TypoScript
	 * @return array An numeric array that contains all available subclasses-strings as values.
	 */
	protected function resolveSubclassesRecursive(array $classesConfiguration, array $subclasses) {
		$allSubclasses = array();
		foreach ($subclasses as $subclass) {
			$allSubclasses[] = $subclass;
			if (isset($classesConfiguration[$subclass]['subclasses']) && is_array($classesConfiguration[$subclass]['subclasses'])) {
				$childSubclasses = $this->resolveSubclassesRecursive($classesConfiguration, $classesConfiguration[$subclass]['subclasses']);
				$allSubclasses = array_merge($allSubclasses, $childSubclasses);
			}
		}
		return $allSubclasses;
	}

	/**
	 * Returns the TCA ctrl section of the specified table; or NULL if not set
	 *
	 * @param string $tableName An optional table name to fetch the columns definition from
	 * @return array The TCA columns definition
	 */
	protected function getControlSection($tableName) {
		return is_array($GLOBALS['TCA'][$tableName]['ctrl']) ? $GLOBALS['TCA'][$tableName]['ctrl'] : NULL;
	}

	/**
	 * Returns the TCA columns array of the specified table
	 *
	 * @param string $tableName An optional table name to fetch the columns definition from
	 * @return array The TCA columns definition
	 */
	protected function getColumnsDefinition($tableName) {
		return is_array($GLOBALS['TCA'][$tableName]['columns']) ? $GLOBALS['TCA'][$tableName]['columns'] : array();
	}

	/**
	 * @param DataMap $dataMap
	 * @param string $tableName
	 * @return DataMap
	 */
	protected function addMetaDataColumnNames(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap $dataMap, $tableName) {
		$controlSection = $GLOBALS['TCA'][$tableName]['ctrl'];
		$dataMap->setPageIdColumnName('pid');
		if (isset($controlSection['tstamp'])) {
			$dataMap->setModificationDateColumnName($controlSection['tstamp']);
		}
		if (isset($controlSection['crdate'])) {
			$dataMap->setCreationDateColumnName($controlSection['crdate']);
		}
		if (isset($controlSection['cruser_id'])) {
			$dataMap->setCreatorColumnName($controlSection['cruser_id']);
		}
		if (isset($controlSection['delete'])) {
			$dataMap->setDeletedFlagColumnName($controlSection['delete']);
		}
		if (isset($controlSection['languageField'])) {
			$dataMap->setLanguageIdColumnName($controlSection['languageField']);
		}
		if (isset($controlSection['transOrigPointerField'])) {
			$dataMap->setTranslationOriginColumnName($controlSection['transOrigPointerField']);
		}
		if (isset($controlSection['type'])) {
			$dataMap->setRecordTypeColumnName($controlSection['type']);
		}
		if (isset($controlSection['rootLevel'])) {
			$dataMap->setRootLevel($controlSection['rootLevel']);
		}
		if (isset($controlSection['is_static'])) {
			$dataMap->setIsStatic($controlSection['is_static']);
		}
		if (isset($controlSection['enablecolumns']['disabled'])) {
			$dataMap->setDisabledFlagColumnName($controlSection['enablecolumns']['disabled']);
		}
		if (isset($controlSection['enablecolumns']['starttime'])) {
			$dataMap->setStartTimeColumnName($controlSection['enablecolumns']['starttime']);
		}
		if (isset($controlSection['enablecolumns']['endtime'])) {
			$dataMap->setEndTimeColumnName($controlSection['enablecolumns']['endtime']);
		}
		if (isset($controlSection['enablecolumns']['fe_group'])) {
			$dataMap->setFrontEndUserGroupColumnName($controlSection['enablecolumns']['fe_group']);
		}
		return $dataMap;
	}

	/**
	 * This method tries to determine the type of type of relation to other tables and sets it based on
	 * the $TCA column configuration
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap $columnMap The column map
	 * @param string $columnConfiguration The column configuration from $TCA
	 * @param array $propertyMetaData The property metadata as delivered by the reflection service
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap
	 */
	protected function setRelations(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap $columnMap, $columnConfiguration, $propertyMetaData) {
		if (isset($columnConfiguration)) {
			if (isset($columnConfiguration['MM'])) {
				$columnMap = $this->setManyToManyRelation($columnMap, $columnConfiguration);
			} elseif (isset($propertyMetaData['elementType'])) {
				$columnMap = $this->setOneToManyRelation($columnMap, $columnConfiguration);
			} elseif (isset($propertyMetaData['type']) && strpbrk($propertyMetaData['type'], '_\\') !== FALSE) {
				$columnMap = $this->setOneToOneRelation($columnMap, $columnConfiguration);
			} else {
				$columnMap->setTypeOfRelation(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_NONE);
			}

		} else {
			$columnMap->setTypeOfRelation(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_NONE);
		}
		return $columnMap;
	}

	/**
	 * Sets field evaluations based on $TCA column configuration.
	 *
	 * @param ColumnMap $columnMap The column map
	 * @param NULL|array $columnConfiguration The column configuration from $TCA
	 * @return ColumnMap
	 */
	protected function setFieldEvaluations(ColumnMap $columnMap, array $columnConfiguration = NULL) {
		if (!empty($columnConfiguration['eval'])) {
			$fieldEvaluations = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $columnConfiguration['eval'], TRUE);
			$dateTimeEvaluations = array('date', 'datetime');

			if (count(array_intersect($dateTimeEvaluations, $fieldEvaluations)) > 0 && !empty($columnConfiguration['dbType'])) {
				$columnMap->setDateTimeStorageFormat($columnConfiguration['dbType']);
			}
		}

		return $columnMap;
	}

	/**
	 * This method sets the configuration for a 1:1 relation based on
	 * the $TCA column configuration
	 *
	 * @param string|\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap $columnMap The column map
	 * @param string $columnConfiguration The column configuration from $TCA
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap
	 */
	protected function setOneToOneRelation(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap $columnMap, $columnConfiguration) {
		$columnMap->setTypeOfRelation(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_ONE);
		$columnMap->setChildTableName($columnConfiguration['foreign_table']);
		$columnMap->setChildTableWhereStatement($columnConfiguration['foreign_table_where']);
		$columnMap->setChildSortByFieldName($columnConfiguration['foreign_sortby']);
		$columnMap->setParentKeyFieldName($columnConfiguration['foreign_field']);
		$columnMap->setParentTableFieldName($columnConfiguration['foreign_table_field']);
		if (is_array($columnConfiguration['foreign_match_fields'])) {
			$columnMap->setRelationTableMatchFields($columnConfiguration['foreign_match_fields']);
		}
		return $columnMap;
	}

	/**
	 * This method sets the configuration for a 1:n relation based on
	 * the $TCA column configuration
	 *
	 * @param string|\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap $columnMap The column map
	 * @param string $columnConfiguration The column configuration from $TCA
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap
	 */
	protected function setOneToManyRelation(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap $columnMap, $columnConfiguration) {
		$columnMap->setTypeOfRelation(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY);
		$columnMap->setChildTableName($columnConfiguration['foreign_table']);
		$columnMap->setChildTableWhereStatement($columnConfiguration['foreign_table_where']);
		$columnMap->setChildSortByFieldName($columnConfiguration['foreign_sortby']);
		$columnMap->setParentKeyFieldName($columnConfiguration['foreign_field']);
		$columnMap->setParentTableFieldName($columnConfiguration['foreign_table_field']);
		if (is_array($columnConfiguration['foreign_match_fields'])) {
			$columnMap->setRelationTableMatchFields($columnConfiguration['foreign_match_fields']);
		}
		return $columnMap;
	}

	/**
	 * This method sets the configuration for a m:n relation based on
	 * the $TCA column configuration
	 *
	 * @param string|\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap $columnMap The column map
	 * @param string $columnConfiguration The column configuration from $TCA
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedRelationException
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap
	 */
	protected function setManyToManyRelation(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap $columnMap, $columnConfiguration) {
		if (isset($columnConfiguration['MM'])) {
			$columnMap->setTypeOfRelation(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY);
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
		} else {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedRelationException('The given information to build a many-to-many-relation was not sufficient. Check your TCA definitions. mm-relations with IRRE must have at least a defined "MM" or "foreign_selector".', 1268817963);
		}
		if ($this->getControlSection($columnMap->getRelationTableName()) !== NULL) {
			$columnMap->setRelationTablePageIdColumnName('pid');
		}
		return $columnMap;
	}

	/**
	 * Creates the ColumnMap object for the given columnName and propertyName
	 *
	 * @param string $columnName
	 * @param string $propertyName
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap
	 */
	protected function createColumnMap($columnName, $propertyName) {
		return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\ColumnMap', $columnName, $propertyName);
	}

}

?>