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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\ClassesConfiguration;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidClassException;
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

    private ColumnMapFactory $columnMapFactory;

    protected string $baseCacheIdentifier;

    public function __construct(
        ReflectionService $reflectionService,
        ConfigurationManagerInterface $configurationManager,
        CacheManager $cacheManager,
        ClassesConfiguration $classesConfiguration,
        ColumnMapFactory $columnMapFactory,
        string $baseCacheIdentifier
    ) {
        $this->reflectionService = $reflectionService;
        $this->configurationManager = $configurationManager;
        $this->cacheManager = $cacheManager;
        $this->dataMapCache = $this->cacheManager->getCache('extbase');
        $this->classesConfiguration = $classesConfiguration;
        $this->columnMapFactory = $columnMapFactory;
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
            $dataMap->addColumnMap(
                $propertyName,
                $this->columnMapFactory->create(
                    $columnName,
                    $columnDefinition,
                    $propertyName,
                    $className
                )
            );
        }
        return $dataMap;
    }

    /**
     * Resolve the table name for the given class name
     *
     * @return string The table name
     */
    protected function resolveTableName(string $className): string
    {
        $className = ltrim($className, '\\');
        $classNameParts = explode('\\', $className);
        // Skip vendor and product name for core classes
        if (str_starts_with($className, 'TYPO3\\CMS\\')) {
            $classPartsToSkip = 2;
        } else {
            $classPartsToSkip = 1;
        }
        $tableName = 'tx_' . strtolower(implode('_', array_slice($classNameParts, $classPartsToSkip)));

        return $tableName;
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
}
