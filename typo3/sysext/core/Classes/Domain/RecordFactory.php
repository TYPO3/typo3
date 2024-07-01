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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
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
 * Creates record objects out of TCA-based database rows,
 * by evaluating the TCA columns, and splits everything which is not a declared column
 * for a TCA type. This is usually the case when a TCA table has a 'typeField' defined,
 * such as "pages", "be_users" and "tt_content".
 *
 * @internal not part of TYPO3 Core API yet.
 */
#[Autoconfigure(public: true)]
readonly class RecordFactory
{
    public function __construct(
        protected TcaSchemaFactory $schemaFactory
    ) {}

    /**
     * Takes a full database record (the whole row), and creates a Record object out of it, based on the type
     * of the record.
     */
    public function createFromDatabaseRow(string $table, array $record): Record
    {
        if (!$this->schemaFactory->has($table)) {
            throw new \InvalidArgumentException(
                'Unable to create Record from non-TCA table "' . $table . '".',
                1715266929
            );
        }
        $schema = $this->schemaFactory->get($table);
        $fullType = $table;
        $properties = [];
        $subSchema = null;
        $typeFieldDefinition = $schema->getSubSchemaDivisorField();
        if ($typeFieldDefinition !== null) {
            if (!isset($record[$typeFieldDefinition->getName()])) {
                throw new \InvalidArgumentException(
                    'Missing typeField "' . $typeFieldDefinition->getName() . '" in record of requested table "' . $table . '".',
                    1715267513,
                );
            }
            $recordType = (string)$record[$typeFieldDefinition->getName()];
            $fullType .= '.' . $recordType;
            $subSchema = $schema->getSubSchema($recordType);
        }
        $computedProperties = $this->extractComputedProperties($record);
        $rawRecord = new RawRecord((int)$record['uid'], (int)$record['pid'], $record, $computedProperties, $fullType);

        // Only use the fields that are defined in the schema
        foreach ($record as $fieldName => $fieldValue) {
            if ($subSchema && !$subSchema->hasField($fieldName)) {
                continue;
            }
            if ($fieldName === $typeFieldDefinition?->getName()) {
                continue;
            }
            $properties[$fieldName] = $fieldValue;
        }
        [$properties, $systemProperties] = $this->extractSystemInformation(
            $schema,
            $rawRecord,
            $properties,
        );
        return new Record($rawRecord, $properties, $systemProperties);
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
            $translationSourceField = $languageCapability->hasTranslationSourceField() ? $languageCapability->getTranslationSourceField()->getName() : null;
            $systemProperties['language'] = new LanguageInfo(
                (int)$rawRecord[$languageField],
                (int)$rawRecord[$transOrigPointerField],
                $translationSourceField ? (int)$rawRecord[$translationSourceField] : null,
            );
            unset($properties[$languageField]);
            unset($properties[$transOrigPointerField]);
            if ($translationSourceField !== null) {
                unset($properties[$translationSourceField]);
            }
            if ($languageCapability->hasDiffSourceField()) {
                unset($properties[$languageCapability->getDiffSourceField()?->getName()]);
            }
            unset($properties['l10n_state']);
        }

        // Workspaces.
        if ($schema->isWorkspaceAware()) {
            $systemProperties['version'] = new VersionInfo(
                (int)$rawRecord['t3ver_wsid'],
                (int)$rawRecord['t3ver_oid'],
                VersionState::tryFrom((int)$rawRecord['t3ver_state']),
                (int)$rawRecord['t3ver_stage'],
            );
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
            // Field is not set in the original record, just skip it
            if (!$rawRecord->isDefined($fieldName)) {
                continue;
            }
            switch ($capability) {
                case TcaSchemaCapability::CreatedAt:
                    $systemProperties['createdAt'] = (new \DateTimeImmutable())->setTimestamp($rawRecord[$fieldName]);
                    break;
                case TcaSchemaCapability::UpdatedAt:
                    $systemProperties['lastUpdatedAt'] = (new \DateTimeImmutable())->setTimestamp($rawRecord[$fieldName]);
                    break;
                case TcaSchemaCapability::RestrictionStartTime:
                    $systemProperties['publishAt'] = (new \DateTimeImmutable())->setTimestamp($rawRecord[$fieldName]);
                    break;
                case TcaSchemaCapability::RestrictionEndTime:
                    $systemProperties['publishUntil'] = (new \DateTimeImmutable())->setTimestamp($rawRecord[$fieldName]);
                    break;

                case TcaSchemaCapability::SoftDelete:
                    $systemProperties['isDeleted'] = (bool)($rawRecord[$fieldName]);
                    break;
                case TcaSchemaCapability::EditLock:
                    $systemProperties['isLockedForEditing'] = (bool)($rawRecord[$fieldName]);
                    break;
                case TcaSchemaCapability::RestrictionDisabledField:
                    $systemProperties['isDisabled'] = (bool)($rawRecord[$fieldName]);
                    break;
                case TcaSchemaCapability::InternalDescription:
                    $systemProperties['description'] = $rawRecord[$fieldName];
                    break;
                case TcaSchemaCapability::SortByField:
                    $systemProperties['sorting'] = (int)($rawRecord[$fieldName]);
                    break;
                case TcaSchemaCapability::RestrictionUserGroup:
                    $systemProperties['userGroupRestriction'] = GeneralUtility::intExplode(
                        ',',
                        $rawRecord[$fieldName],
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
