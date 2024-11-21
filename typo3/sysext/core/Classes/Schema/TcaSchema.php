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

namespace TYPO3\CMS\Core\Schema;

use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Exception\UndefinedFieldException;
use TYPO3\CMS\Core\Schema\Exception\UndefinedSchemaException;
use TYPO3\CMS\Core\Schema\Field\FieldCollection;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\LanguageFieldType;
use TYPO3\CMS\Core\Schema\Field\RelationalFieldTypeInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Main implementation class for TCA-based schema.
 *
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
readonly class TcaSchema implements SchemaInterface
{
    public function __construct(
        protected string $name,
        protected FieldCollection $fields,
        protected array $schemaConfiguration,
        protected ?SchemaCollection $subSchemata = null,
        /** @var PassiveRelation[] */
        protected array $passiveRelations = [],
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getFields(...$fieldNames): FieldCollection
    {
        if ($fieldNames === []) {
            return $this->fields;
        }

        return new FieldCollection(
            array_filter(iterator_to_array($this->fields), static fn(FieldTypeInterface $field): bool => in_array($field->getName(), $fieldNames, true))
        );
    }

    public function hasField(string $fieldName): bool
    {
        return isset($this->fields[$fieldName]);
    }

    public function getField(string $fieldName): FieldTypeInterface
    {
        if (!$this->hasField($fieldName)) {
            throw new UndefinedFieldException('The field "' . $fieldName . '" is not defined for the TCA schema "' . $this->name . '".', 1661615151);
        }
        return $this->fields[$fieldName];
    }

    /**
     * @return FieldTypeInterface[]
     */
    public function getFieldsOfType(TableColumnType $type): iterable
    {
        foreach ($this->fields as $field) {
            if (TableColumnType::tryFrom($field->getType()) !== $type) {
                continue;
            }
            yield $field;
        }
    }

    public function getRawConfiguration(): array
    {
        return $this->schemaConfiguration;
    }

    public function isLanguageAware(): bool
    {
        return isset($this->schemaConfiguration['languageField']) && isset($this->schemaConfiguration['transOrigPointerField']);
    }

    public function isWorkspaceAware(): bool
    {
        return (bool)($this->schemaConfiguration['versioningWS'] ?? false);
    }

    public function hasFieldRestrictions(): bool
    {
        return is_array($this->schemaConfiguration['enablecolumns'] ?? false)
            && $this->schemaConfiguration['enablecolumns'] !== [];

    }

    public function hasCapability(TcaSchemaCapability $capability): bool
    {
        return match ($capability) {
            TcaSchemaCapability::SoftDelete => !empty($this->schemaConfiguration['delete'] ?? null),
            TcaSchemaCapability::CreatedAt => (bool)($this->schemaConfiguration['crdate'] ?? null),
            TcaSchemaCapability::UpdatedAt => (bool)($this->schemaConfiguration['tstamp'] ?? null),
            TcaSchemaCapability::SortByField => !empty($this->schemaConfiguration['sortby'] ?? null),
            TcaSchemaCapability::DefaultSorting => (bool)($this->schemaConfiguration['default_sortby'] ?? null),
            TcaSchemaCapability::AncestorReferenceField => (bool)($this->schemaConfiguration['origUid'] ?? null),

            TcaSchemaCapability::EditLock => isset($this->schemaConfiguration['editlock']) && isset($this->fields[$this->schemaConfiguration['editlock']]),
            TcaSchemaCapability::InternalDescription => isset($this->schemaConfiguration['descriptionColumn']) && isset($this->fields[$this->schemaConfiguration['descriptionColumn']]),

            TcaSchemaCapability::Language => $this->isLanguageAware(),
            TcaSchemaCapability::Workspace => $this->isWorkspaceAware(),

            TcaSchemaCapability::Label => (bool)($this->schemaConfiguration['label'] ?? ''),

            TcaSchemaCapability::AccessAdminOnly => (bool)($this->schemaConfiguration['adminOnly'] ?? false),
            TcaSchemaCapability::AccessReadOnly => (bool)($this->schemaConfiguration['readOnly'] ?? false),
            TcaSchemaCapability::HideRecordsAtCopy => (bool)($this->schemaConfiguration['hideAtCopy'] ?? false),
            TcaSchemaCapability::PrependLabelTextAtCopy => (bool)((string)($this->schemaConfiguration['prependAtCopy'] ?? '')),
            TcaSchemaCapability::RestrictionDisabledField => isset($this->schemaConfiguration['enablecolumns']['disabled']),
            TcaSchemaCapability::RestrictionStartTime => isset($this->schemaConfiguration['enablecolumns']['starttime']),
            TcaSchemaCapability::RestrictionEndTime => isset($this->schemaConfiguration['enablecolumns']['endtime']),
            TcaSchemaCapability::RestrictionUserGroup => isset($this->schemaConfiguration['enablecolumns']['fe_group']),
            // This is an implicit restriction with a custom configuration
            TcaSchemaCapability::RestrictionRootLevel => true,
            TcaSchemaCapability::RestrictionWebMount => !empty($this->schemaConfiguration['security']['ignoreWebMountRestriction'] ?? false),
        };
    }

    /**
     * @return ($capability is TcaSchemaCapability::Language ? Capability\LanguageAwareSchemaCapability
     *          : ($capability is TcaSchemaCapability::RestrictionRootLevel ? Capability\RootLevelCapability
     *          : ($capability is TcaSchemaCapability::EditLock ? Capability\FieldCapability
     *          : ($capability is TcaSchemaCapability::InternalDescription ? Capability\FieldCapability
     *          : ($capability is TcaSchemaCapability::RestrictionDisabledField ? Capability\FieldCapability
     *          : ($capability is TcaSchemaCapability::RestrictionStartTime ? Capability\FieldCapability
     *          : ($capability is TcaSchemaCapability::RestrictionEndTime ? Capability\FieldCapability
     *          : ($capability is TcaSchemaCapability::RestrictionUserGroup ? Capability\FieldCapability
     *          : ($capability is TcaSchemaCapability::PrependLabelTextAtCopy ? Capability\ScalarCapability
     *          : ($capability is TcaSchemaCapability::Label ? Capability\LabelCapability
     *          : Capability\SystemInternalFieldCapability))))))))))
     */
    public function getCapability(TcaSchemaCapability $capability): Capability\SchemaCapabilityInterface
    {
        return match ($capability) {
            TcaSchemaCapability::SoftDelete => new Capability\SystemInternalFieldCapability((string)($this->schemaConfiguration['delete'] ?? '')),
            TcaSchemaCapability::CreatedAt => new Capability\SystemInternalFieldCapability((string)($this->schemaConfiguration['crdate'] ?? '')),
            TcaSchemaCapability::UpdatedAt => new Capability\SystemInternalFieldCapability((string)($this->schemaConfiguration['tstamp'] ?? '')),
            TcaSchemaCapability::SortByField => new Capability\SystemInternalFieldCapability((string)($this->schemaConfiguration['sortby'] ?? '')),
            TcaSchemaCapability::DefaultSorting => new Capability\ScalarCapability((string)($this->schemaConfiguration['default_sortby'] ?? '')),
            TcaSchemaCapability::AncestorReferenceField => new Capability\SystemInternalFieldCapability((string)($this->schemaConfiguration['origUid'] ?? '')),

            TcaSchemaCapability::EditLock => new Capability\FieldCapability($this->fields[$this->schemaConfiguration['editlock']]),
            TcaSchemaCapability::InternalDescription => new Capability\FieldCapability($this->fields[$this->schemaConfiguration['descriptionColumn']]),

            TcaSchemaCapability::Language => $this->buildLanguageCapability(),
            TcaSchemaCapability::Workspace => new Capability\ScalarCapability((bool)($this->schemaConfiguration['versioningWS'] ?? false)),

            TcaSchemaCapability::Label => $this->buildLabelCapability(),

            TcaSchemaCapability::AccessAdminOnly => new Capability\ScalarCapability((bool)($this->schemaConfiguration['adminOnly'] ?? false)),
            TcaSchemaCapability::AccessReadOnly => new Capability\ScalarCapability((bool)($this->schemaConfiguration['readOnly'] ?? false)),
            TcaSchemaCapability::HideRecordsAtCopy => new Capability\ScalarCapability((bool)($this->schemaConfiguration['hideAtCopy'] ?? false)),
            TcaSchemaCapability::PrependLabelTextAtCopy => new Capability\ScalarCapability((string)($this->schemaConfiguration['prependAtCopy'] ?? '')),
            TcaSchemaCapability::RestrictionDisabledField => new Capability\FieldCapability($this->getField($this->schemaConfiguration['enablecolumns']['disabled'])),
            TcaSchemaCapability::RestrictionStartTime => new Capability\FieldCapability($this->getField($this->schemaConfiguration['enablecolumns']['starttime'])),
            TcaSchemaCapability::RestrictionEndTime => new Capability\FieldCapability($this->getField($this->schemaConfiguration['enablecolumns']['endtime'])),
            TcaSchemaCapability::RestrictionUserGroup => new Capability\FieldCapability($this->getField($this->schemaConfiguration['enablecolumns']['fe_group'])),
            TcaSchemaCapability::RestrictionRootLevel => new Capability\RootLevelCapability((int)($this->schemaConfiguration['rootLevel'] ?? 0), $this->schemaConfiguration['security']['ignoreRootLevelRestriction'] ?? false),
            TcaSchemaCapability::RestrictionWebMount => new Capability\ScalarCapability((bool)($this->schemaConfiguration['security']['ignoreWebMountRestriction'] ?? false)),
        };
    }

    protected function buildLanguageCapability(): Capability\LanguageAwareSchemaCapability
    {
        /** @var LanguageFieldType $languageField */
        $languageField = $this->fields[$this->schemaConfiguration['languageField']];
        return new Capability\LanguageAwareSchemaCapability(
            $languageField,
            $this->fields[$this->schemaConfiguration['transOrigPointerField']],
            (isset($this->schemaConfiguration['translationSource']) ? ($this->fields[$this->schemaConfiguration['translationSource']] ?? null) : null),
            (isset($this->schemaConfiguration['transOrigDiffSourceField']) ? ($this->fields[$this->schemaConfiguration['transOrigDiffSourceField']] ?? null) : null),
        );
    }

    protected function buildLabelCapability(): Capability\LabelCapability
    {
        $additionalLabelFields = [];
        if (isset($this->schemaConfiguration['label_alt'])) {
            $additionalFieldNames = GeneralUtility::trimExplode(',', $this->schemaConfiguration['label_alt'], true);
            foreach ($additionalFieldNames as $additionalFieldName) {
                $additionalLabelFields[] = $this->fields[$additionalFieldName];
            }
        }
        $labelConfiguration = [];
        if (isset($this->schemaConfiguration['label_userFunc'])) {
            $labelConfiguration['generator'] = $this->schemaConfiguration['label_userFunc'];
            $labelConfiguration['generatorOptions'] = $this->schemaConfiguration['label_userFunc_options'] ?? [];
        }
        if (isset($this->schemaConfiguration['formattedLabel_userFunc'])) {
            $labelConfiguration['formatter'] = $this->schemaConfiguration['formattedLabel_userFunc'];
            $labelConfiguration['formatterOptions'] = $this->schemaConfiguration['formattedLabel_userFunc_options'] ?? [];
        }
        return new Capability\LabelCapability(
            $this->fields[$this->schemaConfiguration['label']],
            $additionalLabelFields,
            (bool)($this->schemaConfiguration['label_alt_force'] ?? false),
            $labelConfiguration
        );
    }

    public function hasSubSchema(string $subSchema): bool
    {
        return isset($this->subSchemata[$subSchema]);
    }

    public function getSubSchema(string $subSchema): TcaSchema
    {
        if (!$this->hasSubSchema($subSchema)) {
            throw new UndefinedSchemaException('The sub schema "' . $subSchema . '" is not defined for the TCA schema "' . $this->name . '".', 1661617062);
        }

        return $this->subSchemata[$subSchema];
    }

    public function getSubSchemata(): SchemaCollection
    {
        return $this->subSchemata ?? new SchemaCollection([]);
    }

    public function getSubSchemaDivisorField(): ?FieldTypeInterface
    {
        if (isset($this->schemaConfiguration['type']) && isset($this->fields[$this->schemaConfiguration['type']])) {
            return $this->fields[$this->schemaConfiguration['type']];
        }
        return null;
    }

    /**
     * @return PassiveRelation[]
     */
    public function getPassiveRelations(): array
    {
        return $this->passiveRelations;
    }

    /**
     * @return ActiveRelation[]
     */
    public function getActiveRelations(): array
    {
        $relations = [];
        foreach ($this->fields as $field) {
            if ($field instanceof RelationalFieldTypeInterface) {
                $relations = array_merge($relations, $field->getRelations());
            }
        }
        return $relations;
    }

    public static function __set_state(array $state): self
    {
        return new self(...$state);
    }
}
