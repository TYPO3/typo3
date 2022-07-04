<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Extbase\Persistence\Generic\Mapper;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\DataHandling\TableColumnSubType;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\ClassesConfiguration;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidClassException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedRelationException;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchPropertyException;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

/**
 * A factory for a data map to map a single table configured in $TCA on a domain object.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class DataMapFactory implements SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Core\Cache\CacheManager
     */
    protected $cacheManager;

    /**
     * @var FrontendInterface
     */
    protected $dataMapCache;

    /**
     * Runtime cache for data maps, to reduce number of calls to cache backend.
     *
     * @var array
     */
    protected $dataMaps = [];

    /**
     * @var ClassesConfiguration
     */
    private $classesConfiguration;

    protected string $baseCacheIdentifier;

    public function __construct(
        ReflectionService $reflectionService,
        ConfigurationManagerInterface $configurationManager,
        CacheManager $cacheManager,
        ClassesConfiguration $classesConfiguration,
        string $baseCacheIdentifier
    ) {
        $this->reflectionService = $reflectionService;
        $this->configurationManager = $configurationManager;
        $this->cacheManager = $cacheManager;
        $this->dataMapCache = $this->cacheManager->getCache('extbase');
        $this->classesConfiguration = $classesConfiguration;
        $this->baseCacheIdentifier = $baseCacheIdentifier;
    }

    /**
     * Builds a data map by adding column maps for all the configured columns in the $TCA.
     * It also resolves the type of values the column is holding and the typo of relation the column
     * represents.
     *
     * @param string $className The class name you want to fetch the Data Map for
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap The data map
     */
    public function buildDataMap(string $className): DataMap
    {
        $className = ltrim($className, '\\');
        if (isset($this->dataMaps[$className])) {
            return $this->dataMaps[$className];
        }
        $cacheIdentifierClassName = str_replace('\\', '', $className) . '_';
        $cacheIdentifier = 'DataMap_' . $cacheIdentifierClassName . $this->baseCacheIdentifier;
        $dataMap = $this->dataMapCache->get($cacheIdentifier);
        if ($dataMap === false) {
            $dataMap = $this->buildDataMapInternal($className);
            $this->dataMapCache->set($cacheIdentifier, $dataMap);
        }
        $this->dataMaps[$className] = $dataMap;
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
    protected function buildDataMapInternal(string $className): DataMap
    {
        if (!class_exists($className)) {
            throw new InvalidClassException(
                'Could not find class definition for name "' . $className . '". This could be caused by a mis-spelling of the class name in the class definition.',
                1476045117
            );
        }
        $recordType = null;
        $subclasses = [];
        $tableName = $this->resolveTableName($className);
        $fieldNameToPropertyNameMapping = [];
        if ($this->classesConfiguration->hasClass($className)) {
            $classSettings = $this->classesConfiguration->getConfigurationFor($className);
            $subclasses = $this->classesConfiguration->getSubClasses($className);
            if (isset($classSettings['recordType']) && $classSettings['recordType'] !== '') {
                $recordType = $classSettings['recordType'];
            }
            if (isset($classSettings['tableName']) && $classSettings['tableName'] !== '') {
                $tableName = $classSettings['tableName'];
            }
            foreach ($classSettings['properties'] ?? [] as $propertyName => $propertyDefinition) {
                $fieldNameToPropertyNameMapping[$propertyDefinition['fieldName']] = $propertyName;
            }
        }
        $dataMap = GeneralUtility::makeInstance(DataMap::class, $className, $tableName, $recordType, $subclasses);
        $dataMap = $this->addMetaDataColumnNames($dataMap, $tableName);

        foreach ($this->getColumnsDefinition($tableName) as $columnName => $columnDefinition) {
            $propertyName = $fieldNameToPropertyNameMapping[$columnName]
                ?? GeneralUtility::underscoredToLowerCamelCase($columnName);

            // @todo: shall we really create column maps for non existing properties?
            // @todo: check why this could happen in the first place. TCA definitions for non existing model properties?
            $columnMap = $this->createColumnMap($columnName, $propertyName);
            try {
                $property = $this->reflectionService->getClassSchema($className)->getProperty($propertyName);
                [$type, $elementType] = [$property->getType(), $property->getElementType()];
            } catch (NoSuchPropertyException $e) {
                [$type, $elementType] = [null, null];
            }
            $columnMap = $this->setType($columnMap, $columnDefinition['config']);
            $columnMap = $this->setRelations($columnMap, $columnDefinition['config'], $type, $elementType);
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
    protected function resolveTableName(string $className): string
    {
        $className = ltrim($className, '\\');
        $classNameParts = explode('\\', $className);
        // Skip vendor and product name for core classes
        if (strpos($className, 'TYPO3\\CMS\\') === 0) {
            $classPartsToSkip = 2;
        } else {
            $classPartsToSkip = 1;
        }
        $tableName = 'tx_' . strtolower(implode('_', array_slice($classNameParts, $classPartsToSkip)));

        return $tableName;
    }

    /**
     * Returns the TCA ctrl section of the specified table; or NULL if not set
     *
     * @param string $tableName An optional table name to fetch the columns definition from
     * @return array|null The TCA columns definition
     */
    protected function getControlSection(string $tableName): ?array
    {
        return (isset($GLOBALS['TCA'][$tableName]['ctrl']) && is_array($GLOBALS['TCA'][$tableName]['ctrl']))
            ? $GLOBALS['TCA'][$tableName]['ctrl']
            : null;
    }

    /**
     * Returns the TCA columns array of the specified table
     *
     * @param string $tableName An optional table name to fetch the columns definition from
     * @return array The TCA columns definition
     */
    protected function getColumnsDefinition(string $tableName): array
    {
        return is_array($GLOBALS['TCA'][$tableName]['columns'] ?? null) ? $GLOBALS['TCA'][$tableName]['columns'] : [];
    }

    /**
     * @param DataMap $dataMap
     * @param string $tableName
     * @return DataMap
     */
    protected function addMetaDataColumnNames(DataMap $dataMap, string $tableName): DataMap
    {
        $controlSection = $GLOBALS['TCA'][$tableName]['ctrl'] ?? null;
        if ($controlSection === null) {
            return $dataMap;
        }
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
        if (isset($controlSection['transOrigDiffSourceField'])) {
            $dataMap->setTranslationOriginDiffSourceName($controlSection['transOrigDiffSourceField']);
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
     * Set the table column type
     *
     * @param ColumnMap $columnMap
     * @param array $columnConfiguration
     * @return ColumnMap
     */
    protected function setType(ColumnMap $columnMap, array $columnConfiguration): ColumnMap
    {
        // todo: this method should only be called with proper arguments which means that the TCA integrity check should
        // todo: take place outside this method.

        $tableColumnType = $columnConfiguration['type'] ?? null;
        $columnMap->setType(TableColumnType::cast($tableColumnType));
        $tableColumnSubType = null;
        if ($tableColumnType === 'group') {
            $tableColumnSubType = $columnConfiguration['internal_type'] ?? 'db';
        }
        $columnMap->setInternalType(TableColumnSubType::cast($tableColumnSubType));

        return $columnMap;
    }

    /**
     * This method tries to determine the type of type of relation to other tables and sets it based on
     * the $TCA column configuration
     *
     * @param ColumnMap $columnMap The column map
     * @param array|null $columnConfiguration The column configuration from $TCA
     * @param string|null $type
     * @param string|null $elementType
     * @return ColumnMap
     */
    protected function setRelations(ColumnMap $columnMap, ?array $columnConfiguration, ?string $type, ?string $elementType): ColumnMap
    {
        if (isset($columnConfiguration)) {
            if (isset($columnConfiguration['MM'])) {
                $columnMap = $this->setManyToManyRelation($columnMap, $columnConfiguration);
            } elseif ($elementType !== null) {
                $columnMap = $this->setOneToManyRelation($columnMap, $columnConfiguration);
            } elseif ($type !== null && strpbrk($type, '_\\') !== false) {
                // @todo: check the strpbrk function call. Seems to be a check for Tx_Foo_Bar style class names
                $columnMap = $this->setOneToOneRelation($columnMap, $columnConfiguration);
            } elseif (
                isset($columnConfiguration['type'], $columnConfiguration['renderType'])
                && $columnConfiguration['type'] === 'select'
                && (
                    $columnConfiguration['renderType'] !== 'selectSingle'
                    || (isset($columnConfiguration['maxitems']) && $columnConfiguration['maxitems'] > 1)
                )
            ) {
                $columnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_MANY);
            } elseif (
                isset($columnConfiguration['type']) && $columnConfiguration['type'] === 'group'
                && (!isset($columnConfiguration['maxitems']) || $columnConfiguration['maxitems'] > 1)
            ) {
                $columnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_MANY);
            } else {
                $columnMap->setTypeOfRelation(ColumnMap::RELATION_NONE);
            }
        } else {
            $columnMap->setTypeOfRelation(ColumnMap::RELATION_NONE);
        }
        return $columnMap;
    }

    /**
     * Sets field evaluations based on $TCA column configuration.
     *
     * @param ColumnMap $columnMap The column map
     * @param array|null $columnConfiguration The column configuration from $TCA
     * @return ColumnMap
     */
    protected function setFieldEvaluations(ColumnMap $columnMap, array $columnConfiguration = null): ColumnMap
    {
        // todo: this method should only be called with proper arguments which means that the TCA integrity check should
        // todo: take place outside this method.

        if (!empty($columnConfiguration['eval'])) {
            $fieldEvaluations = GeneralUtility::trimExplode(',', $columnConfiguration['eval'], true);
            $dateTimeTypes = QueryHelper::getDateTimeTypes();

            if (!empty(array_intersect($dateTimeTypes, $fieldEvaluations)) && !empty($columnConfiguration['dbType'])) {
                $columnMap->setDateTimeStorageFormat($columnConfiguration['dbType']);
            }
        }

        return $columnMap;
    }

    /**
     * This method sets the configuration for a 1:1 relation based on
     * the $TCA column configuration
     *
     * @param ColumnMap $columnMap The column map
     * @param array|null $columnConfiguration The column configuration from $TCA
     * @return ColumnMap
     */
    protected function setOneToOneRelation(ColumnMap $columnMap, array $columnConfiguration = null): ColumnMap
    {
        // todo: this method should only be called with proper arguments which means that the TCA integrity check should
        // todo: take place outside this method.

        $columnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_ONE);
        // check if foreign_table is set, which usually won't be the case for type "group" fields
        if (!empty($columnConfiguration['foreign_table'])) {
            $columnMap->setChildTableName($columnConfiguration['foreign_table']);
        }
        // todo: don't update column map if value(s) isn't/aren't set.
        $columnMap->setChildSortByFieldName($columnConfiguration['foreign_sortby'] ?? null);
        $columnMap->setParentKeyFieldName($columnConfiguration['foreign_field'] ?? null);
        $columnMap->setParentTableFieldName($columnConfiguration['foreign_table_field'] ?? null);
        if (isset($columnConfiguration['foreign_match_fields']) && is_array($columnConfiguration['foreign_match_fields'])) {
            $columnMap->setRelationTableMatchFields($columnConfiguration['foreign_match_fields']);
        }
        return $columnMap;
    }

    /**
     * This method sets the configuration for a 1:n relation based on
     * the $TCA column configuration
     *
     * @param ColumnMap $columnMap The column map
     * @param array|null $columnConfiguration The column configuration from $TCA
     * @return ColumnMap
     */
    protected function setOneToManyRelation(ColumnMap $columnMap, array $columnConfiguration = null): ColumnMap
    {
        // todo: this method should only be called with proper arguments which means that the TCA integrity check should
        // todo: take place outside this method.

        $columnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_MANY);
        // check if foreign_table is set, which usually won't be the case for type "group" fields
        if (!empty($columnConfiguration['foreign_table'])) {
            $columnMap->setChildTableName($columnConfiguration['foreign_table']);
        }
        // todo: don't update column map if value(s) isn't/aren't set.
        $columnMap->setChildSortByFieldName($columnConfiguration['foreign_sortby'] ?? null);
        $columnMap->setParentKeyFieldName($columnConfiguration['foreign_field'] ?? null);
        $columnMap->setParentTableFieldName($columnConfiguration['foreign_table_field'] ?? null);
        if (isset($columnConfiguration['foreign_match_fields']) && is_array($columnConfiguration['foreign_match_fields'])) {
            $columnMap->setRelationTableMatchFields($columnConfiguration['foreign_match_fields']);
        }
        return $columnMap;
    }

    /**
     * This method sets the configuration for a m:n relation based on
     * the $TCA column configuration
     *
     * @param ColumnMap $columnMap The column map
     * @param array|null $columnConfiguration The column configuration from $TCA
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedRelationException
     * @return ColumnMap
     */
    protected function setManyToManyRelation(ColumnMap $columnMap, array $columnConfiguration = null): ColumnMap
    {
        // todo: this method should only be called with proper arguments which means that the TCA integrity check should
        // todo: take place outside this method.

        if (isset($columnConfiguration['MM'])) {
            $columnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY);
            // check if foreign_table is set, which usually won't be the case for type "group" fields
            if (!empty($columnConfiguration['foreign_table'])) {
                $columnMap->setChildTableName($columnConfiguration['foreign_table']);
            }
            // todo: don't update column map if value(s) isn't/aren't set.
            $columnMap->setRelationTableName($columnConfiguration['MM']);
            if (isset($columnConfiguration['MM_match_fields']) && is_array($columnConfiguration['MM_match_fields'])) {
                $columnMap->setRelationTableMatchFields($columnConfiguration['MM_match_fields']);
            }
            if (isset($columnConfiguration['MM_insert_fields']) && is_array($columnConfiguration['MM_insert_fields'])) {
                $columnMap->setRelationTableInsertFields($columnConfiguration['MM_insert_fields']);
            }
            // todo: don't update column map if value(s) isn't/aren't set.
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
            // todo: this else part is actually superfluous because \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::setRelations
            // todo: only calls this method if $columnConfiguration['MM'] is set.

            throw new UnsupportedRelationException('The given information to build a many-to-many-relation was not sufficient. Check your TCA definitions. mm-relations with IRRE must have at least a defined "MM" or "foreign_selector".', 1268817963);
        }
        $relationTableName = $columnMap->getRelationTableName();
        if ($relationTableName !== null && $this->getControlSection($relationTableName) !== null) {
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
     * @return ColumnMap
     */
    protected function createColumnMap(string $columnName, string $propertyName): ColumnMap
    {
        return GeneralUtility::makeInstance(ColumnMap::class, $columnName, $propertyName);
    }
}
