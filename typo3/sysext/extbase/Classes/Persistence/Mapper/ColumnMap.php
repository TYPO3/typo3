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
 * A column map to map a column configured in $TCA on a property of a domain object.
 *
 * @package Extbase
 * @subpackage Persistence\Mapper
 * @version $ID:$
 */
// SK: PHPDoc ;-)
class Tx_Extbase_Persistence_Mapper_ColumnMap {

	/**
	 * Constants reflecting the type of relation
	 */
	const RELATION_NONE = 'RELATION_NONE';
	const RELATION_HAS_ONE = 'RELATION_HAS_ONE';
	const RELATION_HAS_MANY = 'RELATION_HAS_MANY';
	const RELATION_BELONGS_TO_MANY = 'RELATION_BELONGS_TO_MANY';
	const RELATION_HAS_AND_BELONGS_TO_MANY = 'RELATION_HAS_AND_BELONGS_TO_MANY';

	/**
	 * Constants reflecting how the relation information is stored
	 */
	const RELATION_PARENT_FOREIGN_KEY = 'RELATION_PARENT_FOREIGN_KEY';
	const RELATION_CHILD_FOREIGN_KEY = 'RELATION_CHILD_FOREIGN_KEY';
	const RELATION_PARENT_CSV = 'RELATION_PARENT_CSV';
	const RELATION_INTERMEDIATE_TABLE = 'RELATION_INTERMEDIATE_TABLE';

	/**
	 * Constants reflecting the loading strategy
	 */
	const STRATEGY_EAGER = 'eager';
	const STRATEGY_LAZY_PROXY = 'proxy';
	const STRATEGY_LAZY_STORAGE = 'storage';
	
	/**
	 * The property name corresponding to the table name
	 *
	 * @var string
	 **/
	protected $propertyName;

	/**
	 * The column name
	 *
	 * @var string
	 **/
	protected $columnName;

	/**
	 * The type of relation
	 *
	 * @var int
	 **/
	protected $typeOfRelation;

	/**
	 * The name of the child's class
	 *
	 * @var string
	 **/
	protected $childClassName;

	/**
	 * The name of the child's table
	 *
	 * @var string
	 **/
	protected $childTableName;

	/**
	 * The where clause to narrow down the selected child records
	 *
	 * @var string
	 **/
	protected $childTableWhereStatement;

	/**
	 * The name of the field the results from the child's table are sorted by
	 *
	 * @var string
	 **/
	protected $childSortByFieldName;

	/**
	 * The name of the relation table
	 *
	 * @var string
	 **/
	protected $relationTableName;

	/**
	 * The name of the column  of the relation table holding the page id
	 *
	 * @var string
	 **/
	protected $relationTablePageIdColumnName;

	/**
	 * An array of field => value pairs to both insert and match against when writing/reading MM relations
	 *
	 * @var array
	 **/
	protected $relationTableMatchFields;

	/**
	 * Array of field=>value pairs to insert when writing new MM relations
	 *
	 * @var array
	 **/
	protected $relationTableInsertFields;

	/**
	 * The where clause to narrow down the selected relation table records
	 *
	 * @var string
	 **/
	protected $relationTableWhereStatement;

	/**
	 * The name of the field holding the parents key
	 *
	 * @var string
	 **/
	protected $parentKeyFieldName;

	/**
	 * The name of the field holding the name of the table of the parent's records
	 *
	 * @var string
	 **/
	protected $parentTableFieldName;

	/**
	 * The name of the field holding the children key
	 *
	 * @var string
	 **/
	protected $childKeyFieldName;

	/**
	 * Constructs a Column Map
	 *
	 * @param string $columnName The column name
	 * @param string $propertyName The property name
	 * @return void
	 */
	public function __construct($columnName, $propertyName) {
		// TODO Enable aliases (tx_anotherextension_addedcolumn -> theAddedColumn)
		$this->setColumnName($columnName);
		$this->setPropertyName($propertyName);
	}

	public function setTypeOfRelation($typeOfRelation) {
		$this->typeOfRelation = $typeOfRelation;
	}

	public function getTypeOfRelation() {
		return $this->typeOfRelation;
	}

	public function setPropertyName($propertyName) {
		$this->propertyName = $propertyName;
	}

	public function getPropertyName() {
		return $this->propertyName;
	}

	public function setColumnName($columnName) {
		$this->columnName = $columnName;
	}

	public function getColumnName() {
		return $this->columnName;
	}
	
	public function setChildTableName($childTableName) {
		$this->childTableName = $childTableName;
	}

	public function getChildTableName() {
		return $this->childTableName;
	}

	public function setChildTableWhereStatement($childTableWhereStatement) {
		$this->childTableWhereStatement = $childTableWhereStatement;
	}

	public function getChildTableWhereStatement() {
		return $this->childTableWhereStatement;
	}

	public function setChildSortByFieldName($childSortByFieldName) {
		$this->childSortByFieldName = $childSortByFieldName;
	}

	public function getChildSortByFieldName() {
		return $this->childSortByFieldName;
	}

	public function setRelationTableName($relationTableName) {
		$this->relationTableName = $relationTableName;
	}

	public function getRelationTableName() {
		return $this->relationTableName;
	}

	public function setRelationTablePageIdColumnName($relationTablePageIdColumnName) {
		$this->relationTablePageIdColumnName = $relationTablePageIdColumnName;
	}

	public function getRelationTablePageIdColumnName() {
		return $this->relationTablePageIdColumnName;
	}

	public function setRelationTableMatchFields(array $relationTableMatchFields) {
		$this->relationTableMatchFields = $relationTableMatchFields;
	}

	public function getRelationTableMatchFields() {
		return $this->relationTableMatchFields;
	}

	public function setRelationTableInsertFields(array $relationTableInsertFields) {
		$this->relationTableInsertFields = $relationTableInsertFields;
	}

	public function getRelationTableInsertFields() {
		return $this->relationTableInsertFields;
	}

	public function setRelationTableWhereStatement($relationTableWhereStatement) {
		$this->relationTableWhereStatement = $relationTableWhereStatement;
	}

	public function getRelationTableWhereStatement() {
		return $this->relationTableWhereStatement;
	}

	public function setParentKeyFieldName($parentKeyFieldName) {
		$this->parentKeyFieldName = $parentKeyFieldName;
	}

	public function getParentKeyFieldName() {
		return $this->parentKeyFieldName;
	}

	public function setParentTableFieldName($parentTableFieldName) {
		$this->parentTableFieldName = $parentTableFieldName;
	}

	public function getParentTableFieldName() {
		return $this->parentTableFieldName;
	}

	public function setChildKeyFieldName($childKeyFieldName) {
		$this->childKeyFieldName = $childKeyFieldName;
	}

	public function getChildKeyFieldName() {
		return $this->childKeyFieldName;
	}

}
?>