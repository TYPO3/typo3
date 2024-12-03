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
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
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

    private TcaSchemaFactory $tcaSchemaFactory;

    protected string $baseCacheIdentifier;

    public function __construct(
        ReflectionService $reflectionService,
        ConfigurationManagerInterface $configurationManager,
        CacheManager $cacheManager,
        ClassesConfiguration $classesConfiguration,
        ColumnMapFactory $columnMapFactory,
        TcaSchemaFactory $tcaSchemaFactory,
        string $baseCacheIdentifier
    ) {
        $this->reflectionService = $reflectionService;
        $this->configurationManager = $configurationManager;
        $this->cacheManager = $cacheManager;
        $this->dataMapCache = $this->cacheManager->getCache('extbase');
        $this->classesConfiguration = $classesConfiguration;
        $this->columnMapFactory = $columnMapFactory;
        $this->tcaSchemaFactory = $tcaSchemaFactory;
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
        if (!$this->tcaSchemaFactory->has($tableName)) {
            return $dataMap;
        }
        $schema = $this->tcaSchemaFactory->get($tableName);
        $dataMap = $this->addMetaDataColumnNames($dataMap, $schema);
        foreach ($schema->getFields() as $columnName => $columnDefinition) {
            $propertyName = $fieldNameToPropertyNameMapping[$columnName]
                ?? GeneralUtility::underscoredToLowerCamelCase($columnName);
            $dataMap->addColumnMap(
                $propertyName,
                $this->columnMapFactory->create(
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

    protected function addMetaDataColumnNames(DataMap $dataMap, TcaSchema $schema): DataMap
    {
        $dataMap->setPageIdColumnName('pid');
        if ($schema->hasCapability(TcaSchemaCapability::UpdatedAt)) {
            $dataMap->setModificationDateColumnName((string)$schema->getCapability(TcaSchemaCapability::UpdatedAt));
        }
        if ($schema->hasCapability(TcaSchemaCapability::CreatedAt)) {
            $dataMap->setCreationDateColumnName((string)$schema->getCapability(TcaSchemaCapability::CreatedAt));
        }
        if ($schema->hasCapability(TcaSchemaCapability::SoftDelete)) {
            $dataMap->setDeletedFlagColumnName((string)$schema->getCapability(TcaSchemaCapability::SoftDelete));
        }
        if ($schema->hasCapability(TcaSchemaCapability::Language)) {
            $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
            $dataMap->setLanguageIdColumnName($languageCapability->getLanguageField()->getName());
            $dataMap->setTranslationOriginColumnName($languageCapability->getTranslationOriginPointerField()->getName());
            if ($languageCapability->hasDiffSourceField()) {
                $dataMap->setTranslationOriginDiffSourceName($languageCapability->getDiffSourceField()->getName());
            }
        }
        if ($schema->getSubSchemaDivisorField() !== null) {
            $dataMap->setRecordTypeColumnName($schema->getSubSchemaDivisorField()->getName());
        }
        if ($schema->hasCapability(TcaSchemaCapability::RestrictionRootLevel)) {
            // @todo Evaluate if this is correct. We currently have to use canExistOnPages() to keep previous
            //       behaviour, which is (bool)$rootlevel, so treating "-1" and "1" as TURE, and only 0 als FALSE.
            $dataMap->setRootLevel($schema->getCapability(TcaSchemaCapability::RestrictionRootLevel)->canExistOnPages());
        }
        if (isset($schema->getRawConfiguration()['is_static'])) {
            $dataMap->setIsStatic($schema->getRawConfiguration()['is_static']);
        }
        if ($schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField)) {
            $dataMap->setDisabledFlagColumnName((string)$schema->getCapability(TcaSchemaCapability::RestrictionDisabledField));
        }
        if ($schema->hasCapability(TcaSchemaCapability::RestrictionStartTime)) {
            $dataMap->setStartTimeColumnName((string)$schema->getCapability(TcaSchemaCapability::RestrictionStartTime));
        }
        if ($schema->hasCapability(TcaSchemaCapability::RestrictionEndTime)) {
            $dataMap->setEndTimeColumnName((string)$schema->getCapability(TcaSchemaCapability::RestrictionEndTime));
        }
        if ($schema->hasCapability(TcaSchemaCapability::RestrictionUserGroup)) {
            $dataMap->setFrontEndUserGroupColumnName((string)$schema->getCapability(TcaSchemaCapability::RestrictionUserGroup));
        }
        return $dataMap;
    }
}
