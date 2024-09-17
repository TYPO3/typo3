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

use TYPO3\CMS\Core\Schema\Exception\FieldTypeNotAvailableException;
use TYPO3\CMS\Core\Schema\Field\CategoryFieldType;
use TYPO3\CMS\Core\Schema\Field\CheckboxFieldType;
use TYPO3\CMS\Core\Schema\Field\ColorFieldType;
use TYPO3\CMS\Core\Schema\Field\DateTimeFieldType;
use TYPO3\CMS\Core\Schema\Field\EmailFieldType;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\FileFieldType;
use TYPO3\CMS\Core\Schema\Field\FlexFormFieldType;
use TYPO3\CMS\Core\Schema\Field\FolderFieldType;
use TYPO3\CMS\Core\Schema\Field\GroupFieldType;
use TYPO3\CMS\Core\Schema\Field\ImageManipulationFieldType;
use TYPO3\CMS\Core\Schema\Field\InlineFieldType;
use TYPO3\CMS\Core\Schema\Field\InputFieldType;
use TYPO3\CMS\Core\Schema\Field\JsonFieldType;
use TYPO3\CMS\Core\Schema\Field\LanguageFieldType;
use TYPO3\CMS\Core\Schema\Field\LinkFieldType;
use TYPO3\CMS\Core\Schema\Field\NoneFieldType;
use TYPO3\CMS\Core\Schema\Field\NumberFieldType;
use TYPO3\CMS\Core\Schema\Field\PassthroughFieldType;
use TYPO3\CMS\Core\Schema\Field\PasswordFieldType;
use TYPO3\CMS\Core\Schema\Field\RadioFieldType;
use TYPO3\CMS\Core\Schema\Field\RelationalFieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\SelectRelationFieldType;
use TYPO3\CMS\Core\Schema\Field\SlugFieldType;
use TYPO3\CMS\Core\Schema\Field\StaticSelectFieldType;
use TYPO3\CMS\Core\Schema\Field\TextFieldType;
use TYPO3\CMS\Core\Schema\Field\UserFieldType;
use TYPO3\CMS\Core\Schema\Field\UuidFieldType;

/**
 * Create field objects based on the TCA of the "columns" area.
 *
 * A field type is a class that represents a field in a schema.
 *
 * Currently, the FieldTypes are hard-coded in this class, but in the future, this might be moved
 * into a more flexible registry.
 *
 * Also, the class currently encapsulates the building of the FlexFormSchema (which in turn also has
 * fields), however, since this has some tight coupling this resides here for the time being,
 * but should be extracted later-on.
 *
 * Some interesting points:
 * - the special type "select" is separated into two different classes - one with relations, and one without.
 *
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
class FieldTypeFactory
{
    /**
     * @var array<string, class-string<FieldTypeInterface>>
     */
    protected array $availableFieldTypes = [
        'category' => CategoryFieldType::class,
        'check' => CheckboxFieldType::class,
        'color' => ColorFieldType::class,
        'datetime' => DateTimeFieldType::class,
        'email' => EmailFieldType::class,
        'file' => FileFieldType::class,
        'flex' => FlexFormFieldType::class,
        'folder' => FolderFieldType::class,
        'group' => GroupFieldType::class,
        'imageManipulation' => ImageManipulationFieldType::class,
        'inline' => InlineFieldType::class,
        'input' => InputFieldType::class,
        'json' => JsonFieldType::class,
        'language' => LanguageFieldType::class,
        'link' => LinkFieldType::class,
        'none' => NoneFieldType::class,
        'number' => NumberFieldType::class,
        'passthrough' => PassthroughFieldType::class,
        'password' => PasswordFieldType::class,
        'radio' => RadioFieldType::class,
        'slug' => SlugFieldType::class,
        'text' => TextFieldType::class,
        'user' => UserFieldType::class,
        'uuid' => UuidFieldType::class,
    ];

    public function createFieldType(string $fieldName, array $configuration, string $schemaName, RelationMap $relationMap, ?string $parentSchemaName = null, ?string $parentFieldName = null): FieldTypeInterface
    {
        $fieldType = $configuration['config']['type'] ?? '';
        switch ($fieldType) {
            case 'flex':
                // Build all schemata first
                return $this->createFlexFormField($parentSchemaName ?? $schemaName, $fieldName, $configuration, $relationMap, $parentSchemaName ? $schemaName : null);
            case 'select':
                // In case type "select" is used without any relationship information, it's a static list
                if (RelationshipType::fromTcaConfiguration($configuration) === RelationshipType::Undefined) {
                    return $this->createFromTca(StaticSelectFieldType::class, $fieldName, $configuration);
                }
                return $this->createFromTca(SelectRelationFieldType::class, $fieldName, $configuration, $relationMap->getActiveRelations($parentSchemaName ?? $schemaName, $parentFieldName ?? $fieldName));
            default:
                if ($this->hasFieldType($fieldType)) {
                    $fieldTypeClass = $this->availableFieldTypes[$fieldType];
                    if (is_a($fieldTypeClass, RelationalFieldTypeInterface::class, true)) {
                        return $this->createFromTca($fieldTypeClass, $fieldName, $configuration, $relationMap->getActiveRelations($parentSchemaName ?? $schemaName, $parentFieldName ?? $fieldName));
                    }
                    return $this->createFromTca($fieldTypeClass, $fieldName, $configuration);

                }
                throw new FieldTypeNotAvailableException('Field type "' . $fieldType . '" for field "' . $fieldName . '" not found for schema "' . $schemaName . '".', 1661532580);
        }
    }

    protected function hasFieldType(string $fieldType): bool
    {
        return array_key_exists($fieldType, $this->availableFieldTypes);
    }

    /**
     * Basic factory to create the field type from the TCA configuration via new().
     */
    protected function createFromTca(string $targetClass, string $fieldName, array $fieldConfiguration, ?array $relations = null): FieldTypeInterface
    {
        // We deliberately reduce the "config" subarray to make life easier in the future
        $fieldConfiguration = $this->streamlineFieldConfiguration($fieldConfiguration);
        $arguments = [
            $fieldName,
            $fieldConfiguration,
        ];
        if ($relations !== null) {
            $arguments[] = $relations;
        }

        return new $targetClass(...$arguments);
    }

    /**
     * First, parse the data structures (and if we only have a subschema, we use that one, ofc)
     */
    protected function createFlexFormField(string $mainSchemaName, string $fieldName, array $tcaConfig, RelationMap $relationMap, ?string $subSchemaName = null): FlexFormFieldType
    {
        $tcaConfig = $this->streamlineFieldConfiguration($tcaConfig);
        // This is the place to get all schema / data structures but should be called somewhere else, probably
        // in user-land code
        // @todo: this should go away, or FlexFormSchemaFactory should be removed altogether
        // $flexSchemas = GeneralUtility::makeInstance(FlexFormSchemaFactory::class)->createSchemataForFlexField($tcaConfig, $mainSchemaName, $fieldName, $relationMap);
        return new FlexFormFieldType(
            $fieldName,
            $tcaConfig,
        );
    }

    /**
     * Removes the "config" subkey from TCA, to make it easier to work with the configuration array,
     * also makes caching smaller.
     */
    protected function streamlineFieldConfiguration(array $fieldConfiguration): array
    {
        $configSubArrayInfo = $fieldConfiguration['config'] ?? null;
        unset($fieldConfiguration['config']);
        return array_replace_recursive($configSubArrayInfo ?? [], $fieldConfiguration);
    }
}
