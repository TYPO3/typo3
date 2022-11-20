<?php

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Value object for l10n_state field value.
 */
class State
{
    public const STATE_CUSTOM = 'custom';
    public const STATE_PARENT = 'parent';
    public const STATE_SOURCE = 'source';

    /**
     * @return State|null
     */
    public static function create(string $tableName)
    {
        if (!static::isApplicable($tableName)) {
            return null;
        }

        return GeneralUtility::makeInstance(
            static::class,
            $tableName
        );
    }

    /**
     * @param string|null $json
     * @return State|null
     */
    public static function fromJSON(string $tableName, string $json = null)
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

    /**
     * @return bool
     */
    public static function isApplicable(string $tableName)
    {
        return
            static::hasColumns($tableName)
            && static::hasLanguageFieldName($tableName)
            && static::hasTranslationParentFieldName($tableName)
            && count(static::getFieldNames($tableName)) > 0
        ;
    }

    /**
     * @return array
     */
    public static function getFieldNames(string $tableName)
    {
        return array_keys(
            array_filter(
                $GLOBALS['TCA'][$tableName]['columns'] ?? [],
                static function (array $fieldConfiguration) {
                    return !empty(
                        $fieldConfiguration['config']
                            ['behaviour']['allowLanguageSynchronization']
                    );
                }
            )
        );
    }

    /**
     * @return bool
     */
    protected static function hasColumns(string $tableName)
    {
        return
            !empty($GLOBALS['TCA'][$tableName]['columns'])
            && is_array($GLOBALS['TCA'][$tableName]['columns'])
        ;
    }

    /**
     * @return bool
     */
    protected static function hasLanguageFieldName(string $tableName)
    {
        return !empty($GLOBALS['TCA'][$tableName]['ctrl']['languageField']);
    }

    /**
     * @return bool
     */
    protected static function hasTranslationParentFieldName(string $tableName)
    {
        return !empty($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']);
    }

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var array
     */
    protected $states;

    /**
     * @var array
     */
    protected $originalStates;

    /**
     * @var array
     */
    protected $validStates = [
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

    public function update(array $states)
    {
        $this->states = array_merge(
            $this->states,
            $this->sanitize($states)
        );
    }

    /**
     * Updates field names having a particular state to a target state.
     */
    public function updateStates(string $currentState, string $targetState)
    {
        $states = [];
        foreach ($this->filterFieldNames($currentState) as $fieldName) {
            $states[$fieldName] = $targetState;
        }
        if (!empty($states)) {
            $this->update($states);
        }
    }

    /**
     * @return string|null
     */
    public function export()
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
    public function getModifiedFieldNames()
    {
        return array_keys(
            array_diff_assoc(
                $this->states,
                $this->originalStates
            )
        );
    }

    /**
     * @return bool
     */
    public function isModified()
    {
        return !empty($this->getModifiedFieldNames());
    }

    /**
     * @return bool
     */
    public function isUndefined(string $fieldName)
    {
        return !isset($this->states[$fieldName]);
    }

    /**
     * @return bool
     */
    public function isCustomState(string $fieldName)
    {
        return ($this->states[$fieldName] ?? null) === static::STATE_CUSTOM;
    }

    /**
     * @return bool
     */
    public function isParentState(string $fieldName)
    {
        return ($this->states[$fieldName] ?? null) === static::STATE_PARENT;
    }

    /**
     * @return bool
     */
    public function isSourceState(string $fieldName)
    {
        return ($this->states[$fieldName] ?? null) === static::STATE_SOURCE;
    }

    /**
     * @return string|null
     */
    public function getState(string $fieldName)
    {
        return $this->states[$fieldName] ?? null;
    }

    /**
     * Filters field names having a desired state.
     *
     * @return string[]
     */
    public function filterFieldNames(string $desiredState, bool $modified = false)
    {
        if (!$modified) {
            $fieldNames = array_keys($this->states);
        } else {
            $fieldNames = $this->getModifiedFieldNames();
        }
        return array_filter(
            $fieldNames,
            function ($fieldName) use ($desiredState) {
                return $this->states[$fieldName] === $desiredState;
            }
        );
    }

    /**
     * Filter out field names that don't exist in TCA.
     *
     * @return array
     */
    protected function sanitize(array $states)
    {
        $fieldNames = static::getFieldNames($this->tableName);
        return array_intersect_key(
            $states,
            array_combine($fieldNames, $fieldNames) ?: []
        );
    }

    /**
     * Add missing states for field names.
     *
     * @return array
     */
    protected function enrich(array $states)
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
