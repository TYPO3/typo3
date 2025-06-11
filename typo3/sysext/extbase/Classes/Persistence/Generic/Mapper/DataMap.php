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

/**
 * A data map to map a single table configured in $TCA on a domain object.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
final readonly class DataMap
{
    /**
     * @param string $className Name of the class this column map represents
     * @param string $tableName Name of the DB table this column map is located on
     * @param string|null $recordType The record type stored in the "type" field as configured in $TCA
     * @param array $subclasses List of subclasses of the current class
     * @param array<non-empty-string, ColumnMap> $columnMaps List of TCA columns with their ColumnMap representation
     * @param string|null $languageIdColumnName Name of a column holding the language id of the record, often "sys_language_uid"
     * @param string|null $translationOriginColumnName Name of a column holding the uid of the record this record is a translation of, often "l10n_parent" or "l18n_parent"
     * @param string|null $translationOriginDiffSourceName Name of a column holding the diff data for the record this record is a translation of, often "l10n_diffsource" or "l10n_diffsource"
     * @param string|null $modificationDateColumnName Name of a column holding the timestamp the record was last modified, often "tstamp"
     * @param string|null $creationDateColumnName Name of a column holding the creation date timestamp, often "crdate"
     * @param string|null $deletedFlagColumnName Name of a column indicating the soft deleted state of the row, often "deleted"
     * @param string|null $disabledFlagColumnName Name of a column indicating the "hidden in frontend" state of the row, often "hidden" or "disabled"
     * @param string|null $startTimeColumnName Name of a column holding the timestamp the record should not be displayed before, often "starttime"
     * @param string|null $endTimeColumnName Name of a column holding the timestamp the record should not be displayed afterward, often "endtime"
     * @param string|null $frontendUserGroupColumnName Name of a column holding the uid of the front-end user group which is allowed to edit this record
     * @param string|null $recordTypeColumnName Name of a column holding the record type, example: "CType" in table "tt_content"
     * @param bool $rootLevel Bool cast of TCA[$tableName]['ctrl']['rootLevel']
     */
    public function __construct(
        public string $className,
        public string $tableName,
        public ?string $recordType = null,
        public array $subclasses = [],
        public array $columnMaps = [],
        public ?string $languageIdColumnName = null,
        public ?string $translationOriginColumnName = null,
        public ?string $translationOriginDiffSourceName = null,
        public ?string $modificationDateColumnName = null,
        public ?string $creationDateColumnName = null,
        public ?string $deletedFlagColumnName = null,
        public ?string $disabledFlagColumnName = null,
        public ?string $startTimeColumnName = null,
        public ?string $endTimeColumnName = null,
        public ?string $frontendUserGroupColumnName = null,
        public ?string $recordTypeColumnName = null,
        public bool $rootLevel = false,
    ) {}

    public function getColumnMap(string $propertyName): ?ColumnMap
    {
        return $this->columnMaps[$propertyName] ?? null;
    }

    public function isPersistableProperty(string $propertyName): bool
    {
        return isset($this->columnMaps[$propertyName]);
    }

    // Getters below could be removed but don't harm much and kept as b/w compat for now.

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getRecordType(): ?string
    {
        return $this->recordType;
    }

    public function getSubclasses(): array
    {
        return $this->subclasses;
    }

    public function getLanguageIdColumnName(): ?string
    {
        return $this->languageIdColumnName;
    }

    public function getTranslationOriginColumnName(): ?string
    {
        return $this->translationOriginColumnName;
    }

    public function getTranslationOriginDiffSourceName(): ?string
    {
        return $this->translationOriginDiffSourceName;
    }

    public function getModificationDateColumnName(): ?string
    {
        return $this->modificationDateColumnName;
    }

    public function getCreationDateColumnName(): ?string
    {
        return $this->creationDateColumnName;
    }

    public function getDeletedFlagColumnName(): ?string
    {
        return $this->deletedFlagColumnName;
    }

    public function getDisabledFlagColumnName(): ?string
    {
        return $this->disabledFlagColumnName;
    }

    public function getStartTimeColumnName(): ?string
    {
        return $this->startTimeColumnName;
    }

    public function getEndTimeColumnName(): ?string
    {
        return $this->endTimeColumnName;
    }

    public function getFrontEndUserGroupColumnName(): ?string
    {
        return $this->frontendUserGroupColumnName;
    }

    public function getRecordTypeColumnName(): ?string
    {
        return $this->recordTypeColumnName;
    }

    public function getRootLevel(): bool
    {
        return $this->rootLevel;
    }
}
