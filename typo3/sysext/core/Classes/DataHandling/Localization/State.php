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

namespace TYPO3\CMS\Core\DataHandling\Localization;

use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Value object for l10n_state field value.
 */
class State
{
    public const STATE_CUSTOM = 'custom';
    public const STATE_PARENT = 'parent';
    public const STATE_SOURCE = 'source';

    public static function create(string $tableName): ?State
    {
        if (!static::isApplicable($tableName)) {
            return null;
        }

        return GeneralUtility::makeInstance(
            static::class,
            $tableName
        );
    }

    public static function fromJSON(string $tableName, ?string $json = null): ?State
    {
        if (!static::isApplicable($tableName)) {
            return null;
        }

        $states = json_decode($json ?? '', true);
        return GeneralUtility::makeInstance(
            static::class,
            $tableName,
            $states ?? []
        );
    }

    public static function isApplicable(string $tableName): bool
    {
        $schemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
        return $schemaFactory->has($tableName)
            && $schemaFactory->get($tableName)->isLanguageAware()
            && count(static::getFieldNames($tableName)) > 0;
    }

    /**
     * @return string[]
     */
    public static function getFieldNames(string $tableName): array
    {
        $schemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
        if (!$schemaFactory->has($tableName)) {
            return [];
        }

        return array_map(
            static fn(FieldTypeInterface $field) => $field->getName(),
            iterator_to_array(
                $schemaFactory->get($tableName)->getFields(
                    static fn(FieldTypeInterface $field): bool => !empty($field->getConfiguration()['behaviour']['allowLanguageSynchronization'])
                )
            )
        );
    }

    protected string $tableName;
    protected array $states;
    protected array $originalStates;
    protected array $validStates = [
        self::STATE_CUSTOM,
        self::STATE_SOURCE,
        self::STATE_PARENT,
    ];

    public function __construct(string $tableName, array $states = [])
    {
        $this->tableName = $tableName;
        $this->states = $states;
        $this->originalStates = $states;

        $this->states = $this->enrich(
            $this->sanitize($states)
        );
    }

    public function update(array $states): void
    {
        $this->states = array_merge(
            $this->states,
            $this->sanitize($states)
        );
    }

    /**
     * Updates field names having a particular state to a target state.
     */
    public function updateStates(string $currentState, string $targetState): void
    {
        $states = [];
        foreach ($this->filterFieldNames($currentState) as $fieldName) {
            $states[$fieldName] = $targetState;
        }
        if (!empty($states)) {
            $this->update($states);
        }
    }

    public function export(): string|false|null
    {
        if (empty($this->states)) {
            return null;
        }
        return json_encode($this->states);
    }

    public function toArray(): array
    {
        return $this->states ?? [];
    }

    /**
     * @return string[]
     */
    public function getModifiedFieldNames(): array
    {
        return array_keys(
            array_diff_assoc(
                $this->states,
                $this->originalStates
            )
        );
    }

    public function isModified(): bool
    {
        return !empty($this->getModifiedFieldNames());
    }

    public function isUndefined(string $fieldName): bool
    {
        return !isset($this->states[$fieldName]);
    }

    public function isCustomState(string $fieldName): bool
    {
        return ($this->states[$fieldName] ?? null) === static::STATE_CUSTOM;
    }

    public function isParentState(string $fieldName): bool
    {
        return ($this->states[$fieldName] ?? null) === static::STATE_PARENT;
    }

    public function isSourceState(string $fieldName): bool
    {
        return ($this->states[$fieldName] ?? null) === static::STATE_SOURCE;
    }

    public function getState(string $fieldName): ?string
    {
        return $this->states[$fieldName] ?? null;
    }

    /**
     * Filters field names having a desired state.
     *
     * @return string[]
     */
    public function filterFieldNames(string $desiredState, bool $modified = false): array
    {
        if (!$modified) {
            $fieldNames = array_keys($this->states);
        } else {
            $fieldNames = $this->getModifiedFieldNames();
        }
        return array_filter(
            $fieldNames,
            function (string $fieldName) use ($desiredState): bool {
                return $this->states[$fieldName] === $desiredState;
            }
        );
    }

    /**
     * Filter out field names that don't exist in TCA.
     *
     * @return string[]
     */
    protected function sanitize(array $states): array
    {
        $fieldNames = static::getFieldNames($this->tableName);
        return array_intersect_key(
            $states,
            array_combine($fieldNames, $fieldNames) ?: []
        );
    }

    /**
     * Add missing states for field names.
     */
    protected function enrich(array $states): array
    {
        foreach (static::getFieldNames($this->tableName) as $fieldName) {
            $isValid = in_array(
                $states[$fieldName] ?? null,
                $this->validStates,
                true
            );
            if ($isValid) {
                continue;
            }
            $states[$fieldName] = static::STATE_PARENT;
        }
        return $states;
    }
}
