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

namespace TYPO3\CMS\Extbase\Persistence\Generic\Mapper;

/**
 * A data map to map a single table configured in $TCA on a domain object.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class DataMap
{
    /**
     * The class name
     *
     * @var string
     */
    protected $className;

    /**
     * The table name corresponding to the domain class configured in $TCA
     *
     * @var string
     */
    protected $tableName;

    /**
     * The record type stored in the "type" field as configured in $TCA
     *
     * @var string|null
     */
    protected $recordType;

    /**
     * The subclasses of the current class
     *
     * @var array
     */
    protected $subclasses = [];

    /**
     * An array of column maps configured in $TCA
     *
     * @var array
     */
    protected $columnMaps = [];

    /**
     * @var string
     */
    protected $pageIdColumnName;

    /**
     * @var string
     */
    protected $languageIdColumnName;

    /**
     * @var string
     */
    protected $translationOriginColumnName;

    /**
     * @var string
     */
    protected $translationOriginDiffSourceName;

    /**
     * @var string
     */
    protected $modificationDateColumnName;

    /**
     * @var string
     */
    protected $creationDateColumnName;

    /**
     * @var string
     */
    protected $creatorColumnName;

    /**
     * @var string
     */
    protected $deletedFlagColumnName;

    /**
     * @var string
     */
    protected $disabledFlagColumnName;

    /**
     * @var string
     */
    protected $startTimeColumnName;

    /**
     * @var string
     */
    protected $endTimeColumnName;

    /**
     * @var string
     */
    protected $frontendUserGroupColumnName;

    /**
     * @var string
     */
    protected $recordTypeColumnName;

    /**
     * @var bool
     */
    protected $isStatic = false;

    /**
     * @var bool
     */
    protected $rootLevel = false;

    /**
     * Constructs this DataMap
     *
     * @param string $className The class name
     * @param string $tableName The table name
     * @param string $recordType The record type
     * @param array $subclasses The subclasses
     */
    public function __construct($className, $tableName, $recordType = null, array $subclasses = [])
    {
        $this->setClassName($className);
        $this->setTableName($tableName);
        $this->setRecordType($recordType);
        $this->setSubclasses($subclasses);
    }

    /**
     * Sets the name of the class the column map represents
     *
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * Returns the name of the class the column map represents
     *
     * @return string The class name
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Sets the name of the table the column map represents
     *
     * @param string $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * Returns the name of the table the column map represents
     *
     * @return string The table name
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Sets the record type
     *
     * @param string|null $recordType The record type
     */
    public function setRecordType($recordType)
    {
        $this->recordType = $recordType;
    }

    /**
     * Returns the record type
     *
     * @return string|null The record type
     */
    public function getRecordType()
    {
        return $this->recordType;
    }

    /**
     * Sets the subclasses
     *
     * @param array $subclasses An array of subclasses
     */
    public function setSubclasses(array $subclasses)
    {
        $this->subclasses = $subclasses;
    }

    /**
     * Returns the subclasses
     *
     * @return array The subclasses
     */
    public function getSubclasses()
    {
        return $this->subclasses;
    }

    /**
     * Adds a given column map to the data map.
     *
     * @param ColumnMap $columnMap The column map
     */
    public function addColumnMap(ColumnMap $columnMap)
    {
        $this->columnMaps[$columnMap->getPropertyName()] = $columnMap;
    }

    /**
     * Returns the column map corresponding to the given property name.
     *
     * @param string $propertyName
     * @return ColumnMap|null The column map or NULL if no corresponding column map was found.
     */
    public function getColumnMap($propertyName)
    {
        return $this->columnMaps[$propertyName] ?? null;
    }

    /**
     * Returns TRUE if the property is persistable (configured in $TCA)
     *
     * @param string $propertyName The property name
     * @return bool TRUE if the property is persistable (configured in $TCA)
     */
    public function isPersistableProperty($propertyName)
    {
        return isset($this->columnMaps[$propertyName]);
    }

    /**
     * Sets the name of a column holding the page id
     *
     * @param string $pageIdColumnName The field name
     */
    public function setPageIdColumnName($pageIdColumnName)
    {
        $this->pageIdColumnName = $pageIdColumnName;
    }

    /**
     * Sets the name of a column holding the page id
     *
     * @return string The field name
     */
    public function getPageIdColumnName()
    {
        return $this->pageIdColumnName;
    }

    /**
     * Sets the name of a column holding the language id of the record
     *
     * @param string $languageIdColumnName The field name
     */
    public function setLanguageIdColumnName($languageIdColumnName)
    {
        $this->languageIdColumnName = $languageIdColumnName;
    }

    /**
     * Returns the name of a column holding the language id of the record.
     *
     * @return string The field name
     */
    public function getLanguageIdColumnName()
    {
        return $this->languageIdColumnName;
    }

    /**
     * Sets the name of a column holding the the uid of the record which this record is a translation of.
     *
     * @param string $translationOriginColumnName The field name
     */
    public function setTranslationOriginColumnName($translationOriginColumnName)
    {
        $this->translationOriginColumnName = $translationOriginColumnName;
    }

    /**
     * Returns the name of a column holding the the uid of the record which this record is a translation of.
     *
     * @return string The field name
     */
    public function getTranslationOriginColumnName()
    {
        return $this->translationOriginColumnName;
    }

    /**
     * Sets the name of a column holding the the diff data for the record which this record is a translation of.
     *
     * @param string $translationOriginDiffSourceName The field name
     */
    public function setTranslationOriginDiffSourceName($translationOriginDiffSourceName)
    {
        $this->translationOriginDiffSourceName = $translationOriginDiffSourceName;
    }

    /**
     * Returns the name of a column holding the diff data for the record which this record is a translation of.
     *
     * @return string The field name
     */
    public function getTranslationOriginDiffSourceName()
    {
        return $this->translationOriginDiffSourceName;
    }

    /**
     * Sets the name of a column holding the timestamp the record was modified
     *
     * @param string $modificationDateColumnName The field name
     */
    public function setModificationDateColumnName($modificationDateColumnName)
    {
        $this->modificationDateColumnName = $modificationDateColumnName;
    }

    /**
     * Returns the name of a column holding the timestamp the record was modified
     *
     * @return string The field name
     */
    public function getModificationDateColumnName()
    {
        return $this->modificationDateColumnName;
    }

    /**
     * Sets the name of a column holding the creation date timestamp
     *
     * @param string $creationDateColumnName The field name
     */
    public function setCreationDateColumnName($creationDateColumnName)
    {
        $this->creationDateColumnName = $creationDateColumnName;
    }

    /**
     * Returns the name of a column holding the creation date timestamp
     *
     * @return string The field name
     */
    public function getCreationDateColumnName()
    {
        return $this->creationDateColumnName;
    }

    /**
     * Sets the name of a column holding the uid of the back-end user who created this record
     *
     * @param string $creatorColumnName The field name
     */
    public function setCreatorColumnName($creatorColumnName)
    {
        $this->creatorColumnName = $creatorColumnName;
    }

    /**
     * Returns the name of a column holding the uid of the back-end user who created this record
     *
     * @return string The field name
     */
    public function getCreatorColumnName()
    {
        return $this->creatorColumnName;
    }

    /**
     * Sets the name of a column indicating the 'deleted' state of the row
     *
     * @param string $deletedFlagColumnName The field name
     */
    public function setDeletedFlagColumnName($deletedFlagColumnName)
    {
        $this->deletedFlagColumnName = $deletedFlagColumnName;
    }

    /**
     * Returns the name of a column indicating the 'deleted' state of the row
     *
     * @return string|null The field name
     */
    public function getDeletedFlagColumnName()
    {
        return $this->deletedFlagColumnName;
    }

    /**
     * Sets the name of a column indicating the 'hidden' state of the row
     *
     * @param string $disabledFlagColumnName The field name
     */
    public function setDisabledFlagColumnName($disabledFlagColumnName)
    {
        $this->disabledFlagColumnName = $disabledFlagColumnName;
    }

    /**
     * Returns the name of a column indicating the 'hidden' state of the row
     *
     * @return string The field name
     */
    public function getDisabledFlagColumnName()
    {
        return $this->disabledFlagColumnName;
    }

    /**
     * Sets the name of a column holding the timestamp the record should not displayed before
     *
     * @param string $startTimeColumnName The field name
     */
    public function setStartTimeColumnName($startTimeColumnName)
    {
        $this->startTimeColumnName = $startTimeColumnName;
    }

    /**
     * Returns the name of a column holding the timestamp the record should not displayed before
     *
     * @return string The field name
     */
    public function getStartTimeColumnName()
    {
        return $this->startTimeColumnName;
    }

    /**
     * Sets the name of a column holding the timestamp the record should not displayed afterwards
     *
     * @param string $endTimeColumnName The field name
     */
    public function setEndTimeColumnName($endTimeColumnName)
    {
        $this->endTimeColumnName = $endTimeColumnName;
    }

    /**
     * Returns the name of a column holding the timestamp the record should not displayed afterwards
     *
     * @return string The field name
     */
    public function getEndTimeColumnName()
    {
        return $this->endTimeColumnName;
    }

    /**
     * Sets the name of a column holding the uid of the front-end user group which is allowed to edit this record
     *
     * @param string $frontendUserGroupColumnName The field name
     */
    public function setFrontEndUserGroupColumnName($frontendUserGroupColumnName)
    {
        $this->frontendUserGroupColumnName = $frontendUserGroupColumnName;
    }

    /**
     * Returns the name of a column holding the uid of the front-end user group which is allowed to edit this record
     *
     * @return string The field name
     */
    public function getFrontEndUserGroupColumnName()
    {
        return $this->frontendUserGroupColumnName;
    }

    /**
     * Sets the name of a column holding the record type
     *
     * @param string $recordTypeColumnName The field name
     */
    public function setRecordTypeColumnName($recordTypeColumnName)
    {
        $this->recordTypeColumnName = $recordTypeColumnName;
    }

    /**
     * Sets the name of a column holding the record type
     *
     * @return string The field name
     */
    public function getRecordTypeColumnName()
    {
        return $this->recordTypeColumnName;
    }

    /**
     * @param bool $isStatic
     */
    public function setIsStatic($isStatic)
    {
        $this->isStatic = $isStatic;
    }

    /**
     * @return bool
     */
    public function getIsStatic()
    {
        return $this->isStatic;
    }

    /**
     * @param bool $rootLevel
     */
    public function setRootLevel($rootLevel)
    {
        $this->rootLevel = $rootLevel;
    }

    /**
     * @return bool
     */
    public function getRootLevel()
    {
        return $this->rootLevel;
    }
}
