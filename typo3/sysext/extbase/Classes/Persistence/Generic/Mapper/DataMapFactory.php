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

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ClassesConfiguration;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidClassException;

/**
 * A factory for a data map to map a single table configured in $TCA on a domain object.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
readonly class DataMapFactory
{
    public function __construct(
        private ClassesConfiguration $classesConfiguration,
        private ColumnMapFactory $columnMapFactory,
        private TcaSchemaFactory $tcaSchemaFactory,
        #[Autowire(expression: 'service("package-dependent-cache-identifier").toString()')]
        private string $baseCacheIdentifier,
        #[Autowire(service: 'cache.runtime')]
        private FrontendInterface $firstLevelCache,
        #[Autowire(service: 'cache.extbase')]
        private FrontendInterface $secondLevelCache,
    ) {}

    /**
     * Builds a data map by adding column maps for all the configured columns in the $TCA.
     * It also resolves the type of values the column is holding and the typo of relation the column
     * represents.
     *
     * @param string $className The class name you want to fetch the Data Map for
     */
    public function buildDataMap(string $className): DataMap
    {
        $className = ltrim($className, '\\');
        $cacheIdentifierClassName = str_replace('\\', '', $className) . '_';
        $cacheIdentifier = 'DataMap_' . $cacheIdentifierClassName . $this->baseCacheIdentifier;
        $dataMap = $this->firstLevelCache->get($cacheIdentifier);
        if ($dataMap instanceof DataMap) {
            return $dataMap;
        }
        $dataMap = $this->secondLevelCache->get($cacheIdentifier);
        if ($dataMap instanceof DataMap) {
            $this->firstLevelCache->set($cacheIdentifier, $dataMap);
            return $dataMap;
        }
        $dataMap = $this->buildDataMapInternal($className);
        $this->firstLevelCache->set($cacheIdentifier, $dataMap);
        $this->secondLevelCache->set($cacheIdentifier, $dataMap);
        return $dataMap;
    }

    /**
     * Builds a data map by adding column maps for all the configured columns in the $TCA.
     * It also resolves the type of values the column is holding and the typo of relation the column
     * represents.
     *
     * @param string $className The class name you want to fetch the Data Map for
     * @throws InvalidClassException
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

        $schema = null;
        $languageCapability = null;
        $columnMaps = [];
        if ($this->tcaSchemaFactory->has($tableName)) {
            $schema = $this->tcaSchemaFactory->get($tableName);
            if ($schema->hasCapability(TcaSchemaCapability::Language)) {
                $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
            }
            foreach ($schema->getFields() as $columnName => $columnDefinition) {
                $propertyName = $fieldNameToPropertyNameMapping[$columnName] ?? GeneralUtility::underscoredToLowerCamelCase($columnName);
                $columnMaps[$propertyName] = $this->columnMapFactory->create($columnDefinition, $propertyName, $className);
            }
        }

        return new DataMap(
            className: $className,
            tableName: $tableName,
            recordType: $recordType,
            subclasses: $subclasses,
            columnMaps: $columnMaps,
            languageIdColumnName: $languageCapability?->getLanguageField()->getName(),
            translationOriginColumnName: $languageCapability?->getTranslationOriginPointerField()->getName(),
            translationOriginDiffSourceName: $languageCapability?->hasDiffSourceField()
                ? $languageCapability->getDiffSourceField()->getName()
                : null,
            modificationDateColumnName: $schema?->hasCapability(TcaSchemaCapability::UpdatedAt)
                ? (string)$schema->getCapability(TcaSchemaCapability::UpdatedAt)
                : null,
            creationDateColumnName: $schema?->hasCapability(TcaSchemaCapability::CreatedAt)
                ? (string)$schema->getCapability(TcaSchemaCapability::CreatedAt)
                : null,
            deletedFlagColumnName: $schema?->hasCapability(TcaSchemaCapability::SoftDelete)
                ? (string)$schema->getCapability(TcaSchemaCapability::SoftDelete)
                : null,
            disabledFlagColumnName: $schema?->hasCapability(TcaSchemaCapability::RestrictionDisabledField)
                ? (string)$schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)
                : null,
            startTimeColumnName: $schema?->hasCapability(TcaSchemaCapability::RestrictionStartTime)
                ? (string)$schema->getCapability(TcaSchemaCapability::RestrictionStartTime)
                : null,
            endTimeColumnName: $schema?->hasCapability(TcaSchemaCapability::RestrictionEndTime)
                ? (string)$schema->getCapability(TcaSchemaCapability::RestrictionEndTime)
                : null,
            frontendUserGroupColumnName: $schema?->hasCapability(TcaSchemaCapability::RestrictionUserGroup)
                ? (string)$schema->getCapability(TcaSchemaCapability::RestrictionUserGroup)
                : null,
            recordTypeColumnName: $schema?->getSubSchemaDivisorField()
                ? $schema->getSubSchemaDivisorField()->getName()
                : null,
            isStatic: (bool)($schema?->getRawConfiguration()['is_static'] ?? false),
            // @todo Evaluate if this is correct. We currently have to use canExistOnPages() to keep previous
            //       behavior, which is (bool)$rootLevel, so treating "-1" and "1" as TURE, and only 0 als FALSE.
            rootLevel: $schema?->hasCapability(TcaSchemaCapability::RestrictionRootLevel)
                && $schema->getCapability(TcaSchemaCapability::RestrictionRootLevel)->canExistOnPages(),
        );
    }

    /**
     * Resolve the table name for the given class name
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
        return 'tx_' . strtolower(implode('_', array_slice($classNameParts, $classPartsToSkip)));
    }
}
