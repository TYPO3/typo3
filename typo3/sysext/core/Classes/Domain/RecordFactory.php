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

namespace TYPO3\CMS\Core\Domain;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\DataHandling\RecordFieldTransformer;
use TYPO3\CMS\Core\Domain\Event\RecordCreationEvent;
use TYPO3\CMS\Core\Domain\Exception\IncompleteRecordException;
use TYPO3\CMS\Core\Domain\Exception\RecordPropertyNotFoundException;
use TYPO3\CMS\Core\Domain\Persistence\RecordIdentityMap;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\Domain\Record\LanguageInfo;
use TYPO3\CMS\Core\Domain\Record\SystemProperties;
use TYPO3\CMS\Core\Domain\Record\VersionInfo;
use TYPO3\CMS\Core\Schema\Capability\FieldCapability;
use TYPO3\CMS\Core\Schema\Capability\LanguageAwareSchemaCapability;
use TYPO3\CMS\Core\Schema\Capability\SystemInternalFieldCapability;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Creates record objects out of TCA-based database rows by evaluating the TCA columns and splitting
 * everything which is not a declared column for a TCA type. This is usually the case when a TCA table
 * has a 'typeField' defined, such as "pages", "be_users" and "tt_content".
 *
 * In addition, the RecordFactory can create "Resolved records" by utilizing the RecordFieldTransformer.
 * A "Resolved record" is checked for the actual type (TCA field column type) and is then resolved to
 * - a relation (records, files or folders - wrapped in collections)
 * - an exploded list (e.g. static select)
 * - a FlexForm field
 * - a DateTime field.
 *
 * This means that the field value of a "Resolved Record" is expanded to the actual types (Date objects etc.)
 *
 * @internal not part of TYPO3 Core API yet.
 */
#[Autoconfigure(public: true)]
readonly class RecordFactory
{
    public function __construct(
        protected TcaSchemaFactory $schemaFactory,
        protected RecordFieldTransformer $fieldTransformer,
        protected EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Takes a full database record (the whole row), and creates a Record object out of it,
     * based on the type of the record.
     *
     * This method does not handle special expansion of fields.
     * @todo Now unused - we might want to remove this again
     */
    public function createFromDatabaseRow(string $table, array $record): RecordInterface
    {
        $rawRecord = $this->createRawRecord($table, $record);
        $schema = $this->schemaFactory->get($table);
        $subSchema = null;
        if ($schema->hasSubSchema($rawRecord->getRecordType() ?? '')) {
            $subSchema = $schema->getSubSchema($rawRecord->getRecordType());
            // @todo Support of "subtypes" will most likely be deprecated in upcoming versions
            if ($subSchema->getSubTypeDivisorField() !== null
                && $rawRecord->has($subSchema->getSubTypeDivisorField()->getName())
                && isset($subSchema->getSubSchemata()[$rawRecord->get($subSchema->getSubTypeDivisorField()->getName())])
            ) {
                $subSchema = $subSchema->getSubSchema($rawRecord->get($subSchema->getSubTypeDivisorField()->getName()));
            }
        }

        // Only use the fields that are defined in the schema
        $properties = [];
        foreach ($record as $fieldName => $fieldValue) {
            if ($subSchema && !$subSchema->hasField($fieldName)) {
                continue;
            }
            $properties[$fieldName] = $fieldValue;
        }
        return $this->createRecord($rawRecord, $properties);
    }

    /**
     * Create a "resolved" record. Resolved means that the fields will have
     * their values resolved and extended. A typical use-case is resolving
     * of related records, or using \DateTimeImmutable objects for datetime fields.
     */
    public function createResolvedRecordFromDatabaseRow(string $table, array $record, ?Context $context = null, ?RecordIdentityMap $recordIdentityMap = null): RecordInterface
    {
        $context = $context ?? GeneralUtility::makeInstance(Context::class);
        /** @var RecordIdentityMap $recordIdentityMap */
        $recordIdentityMap = $recordIdentityMap ?? GeneralUtility::makeInstance(RecordIdentityMap::class);
        if ($recordIdentityMap->hasIdentifier($table, (int)($record['uid'] ?? 0))) {
            return $recordIdentityMap->findByIdentifier($table, (int)$record['uid']);
        }
        $properties = [];
        $rawRecord = $this->createRawRecord($table, $record);
        $schema = $this->schemaFactory->get($table);
        $subSchema = null;
        if ($schema->hasSubSchema($rawRecord->getRecordType() ?? '')) {
            $subSchema = $schema->getSubSchema($rawRecord->getRecordType());
            // @todo Support of "subtypes" will most likely be deprecated in upcoming versions
            if ($subSchema->getSubTypeDivisorField() !== null
                && $rawRecord->has($subSchema->getSubTypeDivisorField()->getName())
                && isset($subSchema->getSubSchemata()[$rawRecord->get($subSchema->getSubTypeDivisorField()->getName())])
            ) {
                $subSchema = $subSchema->getSubSchema($rawRecord->get($subSchema->getSubTypeDivisorField()->getName()));
            }
        }

        // Only use the fields that are defined in the schema
        foreach ($record as $fieldName => $fieldValue) {
            if ($subSchema) {
                if (!$subSchema->hasField($fieldName)) {
                    continue;
                }
                $schema = $subSchema;
            } elseif (!$schema->hasField($fieldName)) {
                continue;
            }
            $fieldInformation = $schema->getField($fieldName);
            $properties[$fieldName] = $this->fieldTransformer->transformField(
                $fieldInformation,
                $rawRecord,
                $context,
                $recordIdentityMap
            );
        }
        $resolvedRecord = $this->createRecord($rawRecord, $properties, $context);
        $recordIdentityMap->add($resolvedRecord);
        return $resolvedRecord;
    }

    /**
     * Creates a raw record object from a table and a record array.
     */
    public function createRawRecord(string $table, array $record): RawRecord
    {
        if (!$this->schemaFactory->has($table)) {
            throw new \InvalidArgumentException(
                'Unable to create Record from non-TCA table "' . $table . '".',
                1715266929
            );
        }
        $schema = $this->schemaFactory->get($table);
        $fullType = $table;
        $subSchemaDivisorField = $schema->getSubSchemaDivisorField();
        if ($subSchemaDivisorField !== null) {
            $subSchemaDivisorFieldName = $subSchemaDivisorField->getName();
            if (!isset($record[$subSchemaDivisorFieldName])) {
                throw new \InvalidArgumentException(
                    'Missing typeField "' . $subSchemaDivisorFieldName . '" in record of requested table "' . $table . '".',
                    1715267513,
                );
            }
            $recordType = (string)$record[$subSchemaDivisorFieldName];
            $fullType .= '.' . $recordType;
        }
        $computedProperties = $this->extractComputedProperties($record);
        return new RawRecord((int)$record['uid'], (int)$record['pid'], $record, $computedProperties, $fullType);
    }

    /**
     * Quick helper function in order to avoid duplicate code.
     */
    protected function createRecord(RawRecord $rawRecord, array $properties, ?Context $context = null): RecordInterface
    {
        $context = $context ?? GeneralUtility::makeInstance(Context::class);
        $schema = $this->schemaFactory->get($rawRecord->getMainType());
        [$properties, $systemProperties] = $this->extractSystemInformation(
            $schema,
            $rawRecord,
            $properties,
        );
        $event = new RecordCreationEvent($properties, $rawRecord, $systemProperties, $context);
        $this->eventDispatcher->dispatch($event);
        return $event->isPropagationStopped()
            ? $event->getRecord()
            : new Record($event->getRawRecord(), $event->getProperties(), $event->getSystemProperties());
    }

    protected function extractComputedProperties(array &$record): ComputedProperties
    {
        $computedProperties = new ComputedProperties(
            $record['_ORIG_uid'] ?? null,
            $record['_LOCALIZED_UID'] ?? null,
            $record['_REQUESTED_OVERLAY_LANGUAGE'] ?? null,
            $record['_TRANSLATION_SOURCE'] ?? null
        );
        unset(
            $record['_ORIG_uid'],
            $record['_LOCALIZED_UID'],
            $record['_REQUESTED_OVERLAY_LANGUAGE'],
            $record['_TRANSLATION_SOURCE']
        );
        return $computedProperties;
    }

    protected function extractSystemInformation(TcaSchema $schema, RawRecord $rawRecord, array $properties): array
    {
        // Language information.
        $systemProperties = [];
        if ($schema->isLanguageAware()) {
            /** @var LanguageAwareSchemaCapability $languageCapability */
            $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
            $languageField = $languageCapability->getLanguageField()->getName();
            $transOrigPointerField = $languageCapability->getTranslationOriginPointerField()->getName();
            $translationSourceField = $languageCapability->hasTranslationSourceField() ? $languageCapability->getTranslationSourceField()->getName() : '';
            try {
                $systemProperties['language'] = new LanguageInfo(
                    (int)$rawRecord->get($languageField),
                    (int)$rawRecord->get($transOrigPointerField),
                    $rawRecord->has($translationSourceField) ? (int)$rawRecord->get($translationSourceField) : null,
                );
            } catch (RecordPropertyNotFoundException $e) {
                throw new IncompleteRecordException(
                    'Table "' . $schema->getName() . '" is defined as language aware but the record misses necessary fields: ' . $e->getMessage(),
                    1726046917
                );
            }
            unset($properties[$languageField]);
            unset($properties[$transOrigPointerField]);
            if ($translationSourceField !== '') {
                unset($properties[$translationSourceField]);
            }
            if ($languageCapability->hasDiffSourceField()) {
                unset($properties[$languageCapability->getDiffSourceField()?->getName()]);
            }
            unset($properties['l10n_state']);
        }

        // Workspaces.
        if ($schema->isWorkspaceAware()) {
            try {
                $systemProperties['version'] = new VersionInfo(
                    (int)$rawRecord->get('t3ver_wsid'),
                    (int)$rawRecord->get('t3ver_oid'),
                    VersionState::tryFrom((int)$rawRecord->get('t3ver_state')),
                    (int)$rawRecord->get('t3ver_stage'),
                );
            } catch (RecordPropertyNotFoundException $e) {
                throw new IncompleteRecordException(
                    'Table "' . $schema->getName() . '" is defined as workspace aware but the record misses necessary fields: ' . $e->getMessage(),
                    1726046918
                );
            }
            unset(
                $properties['t3ver_wsid'],
                $properties['t3ver_oid'],
                $properties['t3ver_state'],
                $properties['t3ver_stage']
            );
        }

        // Date-related fields
        foreach (TcaSchemaCapability::getSystemCapabilities() as $capability) {
            if (!$schema->hasCapability($capability)) {
                continue;
            }
            /** @var SystemInternalFieldCapability|FieldCapability $capabilityInstance */
            $capabilityInstance = $schema->getCapability($capability);
            $fieldName = $capabilityInstance->getFieldName();
            if (!$rawRecord->has($fieldName)) {
                throw new IncompleteRecordException(
                    'Table "' . $schema->getName() . '" has capability "' . $capability->name . '" set but the record misses the corresponding field "' . $fieldName . '"',
                    1726046919
                );
            }
            switch ($capability) {
                case TcaSchemaCapability::CreatedAt:
                    $systemProperties['createdAt'] = (new \DateTimeImmutable())->setTimestamp($rawRecord->get($fieldName));
                    break;
                case TcaSchemaCapability::UpdatedAt:
                    $systemProperties['lastUpdatedAt'] = (new \DateTimeImmutable())->setTimestamp($rawRecord->get($fieldName));
                    break;
                case TcaSchemaCapability::RestrictionStartTime:
                    $systemProperties['publishAt'] = (new \DateTimeImmutable())->setTimestamp($rawRecord->get($fieldName));
                    break;
                case TcaSchemaCapability::RestrictionEndTime:
                    $systemProperties['publishUntil'] = (new \DateTimeImmutable())->setTimestamp($rawRecord->get($fieldName));
                    break;

                case TcaSchemaCapability::SoftDelete:
                    $systemProperties['isDeleted'] = (bool)($rawRecord->get($fieldName));
                    break;
                case TcaSchemaCapability::EditLock:
                    $systemProperties['isLockedForEditing'] = (bool)($rawRecord->get($fieldName));
                    break;
                case TcaSchemaCapability::RestrictionDisabledField:
                    $systemProperties['isDisabled'] = (bool)($rawRecord->get($fieldName));
                    break;
                case TcaSchemaCapability::InternalDescription:
                    $systemProperties['description'] = $rawRecord->get($fieldName);
                    break;
                case TcaSchemaCapability::SortByField:
                    $systemProperties['sorting'] = (int)($rawRecord->get($fieldName));
                    break;
                case TcaSchemaCapability::RestrictionUserGroup:
                    $systemProperties['userGroupRestriction'] = GeneralUtility::intExplode(
                        ',',
                        $rawRecord->get($fieldName),
                        true
                    );
                    break;
            }
            unset($properties[$fieldName]);
        }

        $systemProperties = new SystemProperties(
            $systemProperties['language'] ?? null,
            $systemProperties['version'] ?? null,
            $systemProperties['isDeleted'] ?? null,
            $systemProperties['isDisabled'] ?? null,
            $systemProperties['isLockedForEditing'] ?? null,
            $systemProperties['createdAt'] ?? null,
            $systemProperties['lastUpdatedAt'] ?? null,
            $systemProperties['publishAt'] ?? null,
            $systemProperties['publishUntil'] ?? null,
            $systemProperties['userGroupRestriction'] ?? null,
            $systemProperties['sorting'] ?? null,
            $systemProperties['description'] ?? null,
        );
        return [$properties, $systemProperties];
    }
}
