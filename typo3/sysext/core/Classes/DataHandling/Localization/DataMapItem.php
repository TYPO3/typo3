<?php
namespace TYPO3\CMS\Core\DataHandling\Localization;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Entity for data-map item.
 */
class DataMapItem
{
    const TYPE_PARENT = 'parent';
    const TYPE_DIRECT_CHILD = 'directChild';
    const TYPE_GRAND_CHILD = 'grandChild';

    const SCOPE_PARENT = State::STATE_PARENT;
    const SCOPE_SOURCE = State::STATE_SOURCE;
    const SCOPE_EXCLUDE = 'exclude';

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var string|int
     */
    protected $id;

    /**
     * @var array
     */
    protected $suggestedValues;

    /**
     * @var array
     */
    protected $persistedValues;

    /**
     * @var array
     */
    protected $configurationFieldNames;

    /**
     * @var bool
     */
    protected $new;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var string|int
     */
    protected $language;

    /**
     * @var string|int
     */
    protected $parent;

    /**
     * @var string|int
     */
    protected $source;

    /**
     * @var DataMapItem[][]
     */
    protected $dependencies = [];

    /**
     * Builds a data-map item. In addition to the constructor, the values
     * for language, parent and source record pointers are assigned as well.
     *
     * @param string $tableName
     * @param string|int $id
     * @param array $suggestedValues
     * @param array $persistedValues
     * @param array $configurationFieldNames
     * @return object|DataMapItem
     */
    public static function build(
        string $tableName,
        $id,
        array $suggestedValues,
        array $persistedValues,
        array $configurationFieldNames
    ) {
        $item = GeneralUtility::makeInstance(
            static::class,
            $tableName,
            $id,
            $suggestedValues,
            $persistedValues,
            $configurationFieldNames
        );

        $item->language = (int)($suggestedValues[$item->getLanguageFieldName()] ?? $persistedValues[$item->getLanguageFieldName()]);
        $item->setParent($suggestedValues[$item->getParentFieldName()] ?? $persistedValues[$item->getParentFieldName()]);
        if ($item->getSourceFieldName() !== null) {
            $item->setSource($suggestedValues[$item->getSourceFieldName()] ?? $persistedValues[$item->getSourceFieldName()]);
        }

        return $item;
    }

    /**
     * @param string $tableName
     * @param string|int $id
     * @param array $suggestedValues
     * @param array $persistedValues
     * @param array $configurationFieldNames
     */
    public function __construct(
        string $tableName,
        $id,
        array $suggestedValues,
        array $persistedValues,
        array $configurationFieldNames
    ) {
        $this->tableName = $tableName;
        $this->id = $id;

        $this->suggestedValues = $suggestedValues;
        $this->persistedValues = $persistedValues;
        $this->configurationFieldNames = $configurationFieldNames;

        $this->new = !MathUtility::canBeInterpretedAsInteger($id);
    }

    /**
     * Gets the current table name of this data-map item.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Gets the table name used to resolve the language parent record.
     *
     * @return string
     */
    public function getFromTableName(): string
    {
        if ($this->tableName === 'pages_language_overlay') {
            return 'pages';
        }
        return $this->tableName;
    }

    /**
     * Gets the table name used to resolve any kind of translations.
     *
     * @return string
     */
    public function getForTableName(): string
    {
        if ($this->tableName === 'pages') {
            return 'pages_language_overlay';
        }
        return $this->tableName;
    }

    /**
     * Gets the id of this data-map item.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the suggested values that were initially
     * submitted as the whole data-map to the DataHandler.
     *
     * @return array
     */
    public function getSuggestedValues(): array
    {
        return $this->suggestedValues;
    }

    /**
     * Gets the persisted values that represent the persisted state
     * of the record this data-map item is a surrogate for - does only
     * contain relevant field values.
     *
     * @return array
     */
    public function getPersistedValues(): array
    {
        return $this->persistedValues;
    }

    /**
     * @return array
     */
    public function getConfigurationFieldNames(): array
    {
        return $this->configurationFieldNames;
    }

    /**
     * @return string
     */
    public function getLanguageFieldName(): string
    {
        return $this->configurationFieldNames['language'];
    }

    /**
     * @return string
     */
    public function getParentFieldName(): string
    {
        return $this->configurationFieldNames['parent'];
    }

    /**
     * @return null|string
     */
    public function getSourceFieldName()
    {
        return $this->configurationFieldNames['source'];
    }

    /**
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->new;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        if ($this->type === null) {
            // implicit: default language, it's a parent
            if ($this->language === 0) {
                $this->type = static::TYPE_PARENT;
            // implicit: having source value different to parent value, it's a 2nd or higher level translation
            } elseif (
                $this->source !== null
                && $this->source !== $this->parent
            ) {
                $this->type = static::TYPE_GRAND_CHILD;
            // implicit: otherwise, it's a 1st level translation
            } else {
                $this->type = static::TYPE_DIRECT_CHILD;
            }
        }
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isParentType(): bool
    {
        return $this->getType() === static::TYPE_PARENT;
    }

    /**
     * @return bool
     */
    public function isDirectChildType(): bool
    {
        return $this->getType() === static::TYPE_DIRECT_CHILD;
    }

    /**
     * @return bool
     */
    public function isGrandChildType(): bool
    {
        return $this->getType() === static::TYPE_GRAND_CHILD;
    }

    /**
     * @return State
     */
    public function getState(): State
    {
        if ($this->state === null && !$this->isParentType()) {
            $this->state = State::fromJSON(
                $this->tableName,
                $this->persistedValues['l10n_state'] ?? null
            );
            $this->state->update(
                $this->suggestedValues['l10n_state'] ?? []
            );
        }
        return $this->state;
    }

    /**
     * @return string|int
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string|int $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return string|int
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param string|int $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return string|int
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string|int $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @param string $scope
     * @return int|string
     */
    public function getIdForScope($scope)
    {
        if (
            $scope === static::SCOPE_PARENT
            || $scope === static::SCOPE_EXCLUDE
        ) {
            return $this->getParent();
        }
        if ($scope === static::SCOPE_SOURCE) {
            return $this->getSource();
        }
        throw new \RuntimeException('Invalid scope', 1486325248);
    }

    /**
     * @return DataMapItem[][]
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * @param DataMapItem[][] $dependencies
     */
    public function setDependencies(array $dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @param string $scope
     * @return DataMapItem[]
     */
    public function findDependencies(string $scope)
    {
        return ($this->dependencies[$scope] ?? []);
    }

    /**
     * @return string[]
     */
    public function getApplicableScopes()
    {
        $scopes = [];
        if (!empty($this->getSourceFieldName())) {
            $scopes[] = static::SCOPE_SOURCE;
        }
        $scopes[] = static::SCOPE_PARENT;
        $scopes[] = static::SCOPE_EXCLUDE;
        return $scopes;
    }
}