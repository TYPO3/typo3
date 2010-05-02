<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * A data map to map a single table configured in $TCA on a domain object.
 *
 * @package Extbase
 * @subpackage Persistence\Mapper
 * @version $ID:$
 */
class Tx_Extbase_Persistence_Mapper_DataMap {
	
	/**
	 * The class name
	 *
	 * @var string
	 **/
	protected $className;

	/**
	 * The table name corresponding to the domain class configured in $TCA
	 *
	 * @var string
	 **/
	protected $tableName;

	/**
	 * The record type stored in the "type" field as configured in $TCA
	 *
	 * @var string
	 **/
	protected $recordType;

	/**
	 * The subclasses of the current class
	 *
	 * @var array
	 **/
	protected $subclasses = array();

	/**
	 * An array of column maps configured in $TCA
	 *
	 * @var array
	 **/
	protected $columnMaps = array();
		
	/**
	 * @var string
	 **/
	protected $pageIdColumnName;

	/**
	 * @var string
	 **/
	protected $languageIdColumnName;

	/**
	 * @var string
	 **/
	protected $translationOriginColumnName;

	/**
	 * @var string
	 **/
	protected $modificationDateColumnName;

	/**
	 * @var string
	 **/
	protected $creationDateColumnName;

	/**
	 * @var string
	 **/
	protected $creatorColumnName;

	/**
	 * @var string
	 **/
	protected $deletedFlagColumnName;

	/**
	 * @var string
	 **/
	protected $disabledFlagColumnName;
	
	/**
	 * @var string
	 **/
	protected $startTimeColumnName;

	/**
	 * @var string
	 **/
	protected $endTimeColumnName;

	/**
	 * @var string
	 **/
	protected $frontendUserGroupColumnName;

	/**
	 * @var string
	 **/
	protected $recordTypeColumnName;

	/**
	 * Constructs this DataMap
	 *
	 * @param string $className The class name
	 * @param string $tableName The table name
	 * @param string $recordType The record type
	 * @param array $subclasses The subclasses
	 */
	public function __construct($className, $tableName, $recordType = NULL, array $subclasses = array()) {
		$this->setClassName($className);
		$this->setTableName($tableName);
		$this->setRecordType($recordType);
		$this->setSubclasses($subclasses);
	}

	/**
	 * Sets the name of the class the colum map represents
	 *
	 * @return void
	 */
	public function setClassName($className) {
		$this->className = $className;
	}

	/**
	 * Returns the name of the class the column map represents
	 *
	 * @return string The class name
	 */
	public function getClassName() {
		return $this->className;
	}

	/**
	 * Sets the name of the table the colum map represents
	 *
	 * @return void
	 */
	public function setTableName($tableName) {
		$this->tableName = $tableName;
	}

	/**
	 * Returns the name of the table the column map represents
	 *
	 * @return string The table name
	 */
	public function getTableName() {
		return $this->tableName;
	}
	
	/**
	 * Sets the record type
	 *
	 * @param string $recordType The record type
	 * @return void
	 */
	public function setRecordType($recordType) {
		$this->recordType = $recordType;
	}

	/**
	 * Returns the record type
	 *
	 * @return string The record type
	 */
	public function getRecordType() {
		return $this->recordType;
	}
	
	/**
	 * Sets the subclasses
	 *
	 * @param array $subclasses An array of subclasses
	 * @return void
	 */
	public function setSubclasses(array $subclasses) {
		$this->subclasses = $subclasses;
	}

	/**
	 * Returns the subclasses
	 *
	 * @return array The subclasses
	 */
	public function getSubclasses() {
		return $this->subclasses;
	}
	
	/**
	 * Adds a given column map to the data map.
	 *
	 * @param Tx_Extbase_Persistence_Mapper_ColumnMap $columnMap The column map
	 * @return void
	 */
	public function addColumnMap(Tx_Extbase_Persistence_Mapper_ColumnMap $columnMap) {
		$this->columnMaps[$columnMap->getPropertyName()] = $columnMap;
	}
	
	/**
	 * Returns the column map corresponding to the given property name.
	 *
	 * @param string $propertyName
	 * @return Tx_Extbase_Persistence_Mapper_ColumnMap|NULL The column map or NULL if no corresponding column map was found.
	 */
	public function getColumnMap($propertyName) {
		return $this->columnMaps[$propertyName];
	}

	/**
	 * Returns TRUE if the property is persistable (configured in $TCA)
	 *
	 * @param string $propertyName The property name
	 * @return boolean TRUE if the property is persistable (configured in $TCA)
	 */
	public function isPersistableProperty($propertyName) {
		return isset($this->columnMaps[$propertyName]);
	}
	
	
	/**
	 * Sets the name of a column holding the page id
	 *
	 * @param string The field name
	 * @return void
	 */
	public function setPageIdColumnName($pageIdColumnName) {
		$this->pageIdColumnName = $pageIdColumnName;
	}
	
	/**
	 * Sets the name of a column holding the page id
	 *
	 * @return string The field name
	 */
	public function getPageIdColumnName() {
		return $this->pageIdColumnName;
	}
	
 	/**
 	 * Sets the name of a column holding the language id of the record
 	 *
 	 * @param string $languageIdColumnName The field name
 	 * @return void
 	 */
 	public function setLanguageIdColumnName($languageIdColumnName) {
 		$this->languageIdColumnName = $languageIdColumnName;
 	}
		
 	/**
 	 * Returns the name of a column holding the language id of the record.
 	 *
 	 * @return string The field name
 	 */
 	public function getLanguageIdColumnName() {
 		return $this->languageIdColumnName;
 	}
	
 	/**
 	 * Sets the name of a column holding the the uid of the record which this record is a translation of.
 	 *
 	 * @param string $translationOriginColumnName The field name
 	 * @return void
 	 */
 	public function setTranslationOriginColumnName($translationOriginColumnName) {
 		$this->translationOriginColumnName = $translationOriginColumnName;
 	}
		
 	/**
 	 * Returns the name of a column holding the the uid of the record which this record is a translation of.
 	 *
 	 * @return string The field name
 	 */
 	public function getTranslationOriginColumnName() {
 		return $this->translationOriginColumnName;
 	}
	
	/**
	 * Sets the name of a column holding the timestamp the record was modified
	 *
	 * @param string The field name
	 * @return void
	 */
	public function setModificationDateColumnName($modificationDateColumnName) {
		$this->modificationDateColumnName = $modificationDateColumnName;
	}
	
	/**
	 * Returns the name of a column holding the timestamp the record was modified
	 *
	 * @return string The field name
	 */
	public function getModificationDateColumnName() {
		return $this->modificationDateColumnName;
	}
	
	/**
	 * Sets the name of a column holding the creation date timestamp
	 *
	 * @param string The field name
	 * @return void
	 */
	public function setCreationDateColumnName($creationDateColumnName) {
		$this->creationDateColumnName = $creationDateColumnName;
	}
	
	/**
	 * Returns the name of a column holding the creation date timestamp
	 *
	 * @return string The field name
	 */
	public function getCreationDateColumnName() {
		return $this->creationDateColumnName;
	}
	
	/**
	 * Sets the name of a column holding the uid of the back-end user who created this record
	 *
	 * @param string The field name
	 * @return void
	 */
	public function setCreatorColumnName($creatorColumnName) {
		$this->creatorColumnName = $creatorColumnName;
	}

	/**
	 * Returns the name of a column holding the uid of the back-end user who created this record
	 *
	 * @return string The field name
	 */
	public function getCreatorColumnName() {
		return $this->creatorColumnName;
	}

	/**
	 * Sets the name of a column indicating the 'deleted' state of the row
	 *
	 * @param string The field name
	 * @return void
	 */
	public function setDeletedFlagColumnName($deletedFlagColumnName) {
		$this->deletedFlagColumnName = $deletedFlagColumnName;
	}
	
	/**
	 * Returns the name of a column indicating the 'deleted' state of the row
	 *
	 * @return string The field name
	 */
	public function getDeletedFlagColumnName() {
		return $this->deletedFlagColumnName;
	}
	
	/**
	 * Sets the name of a column indicating the 'hidden' state of the row
	 *
	 * @param string The field name
	 * @return void
	 */
	public function setDisabledFlagColumnName($disabledFlagColumnName) {
		$this->disabledFlagColumnName = $disabledFlagColumnName;
	}
	
	/**
	 * Returns the name of a column indicating the 'hidden' state of the row
	 *
	 * @return string The field name
	 */
	public function getDisabledFlagColumnName() {
		return $this->disabledFlagColumnName;
	}
	
	/**
	 * Sets the name of a column holding the timestamp the record should not displayed before
	 *
	 * @param string The field name
	 * @return void
	 */
	public function setStartTimeColumnName($startTimeColumnName) {
		$this->startTimeColumnName = $startTimeColumnName;
	}

	/**
	 * Returns the name of a column holding the timestamp the record should not displayed before
	 *
	 * @return string The field name
	 */
	public function getStartTimeColumnName() {
		return $this->startTimeColumnName;
	}

	/**
	 * Sets the name of a column holding the timestamp the record should not displayed afterwards
	 *
	 * @param string The field name
	 * @return void
	 */
	public function setEndTimeColumnName($endTimeColumnName) {
		$this->endTimeColumnName = $endTimeColumnName;
	}

	/**
	 * Returns the name of a column holding the timestamp the record should not displayed afterwards
	 *
	 * @return string The field name
	 */
	public function getEndTimeColumnName() {
		return $this->endTimeColumnName;
	}

	/**
	 * Sets the name of a column holding the uid of the front-end user group which is allowed to edit this record
	 *
	 * @param string The field name
	 * @return void
	 */
	public function setFrontEndUserGroupColumnName($frontendUserGroupColumnName) {
		$this->frontendUserGroupColumnName = $frontendUserGroupColumnName;
	}

	/**
	 * Returns the name of a column holding the uid of the front-end user group which is allowed to edit this record
	 *
	 * @return string The field name
	 */
	public function getFrontEndUserGroupColumnName() {
		return $this->frontendUserGroupColumnName;
	}

	/**
	 * Sets the name of a column holding the record type
	 *
	 * @param string $recordTypeColumnName The field name
	 * @return void
	 */
	public function setRecordTypeColumnName($recordTypeColumnName) {
		$this->recordTypeColumnName = $recordTypeColumnName;
	}

	/**
	 * Sets the name of a column holding the record type
	 *
	 * @return string The field name
	 */
	public function getRecordTypeColumnName() {
		return $this->recordTypeColumnName;
	}

}