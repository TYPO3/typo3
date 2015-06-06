<?php
namespace TYPO3\CMS\Dbal\Database\Specifics;

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

/**
 * This class handles the specifics of the active DBMS. Inheriting classes
 * are intended to define their own specifics.
 */
abstract class AbstractSpecifics {
	/**
	 * Constants used as identifiers in $specificProperties.
	 */
	const TABLE_MAXLENGTH = 'table_maxlength';
	const FIELD_MAXLENGTH = 'field_maxlength';
	const LIST_MAXEXPRESSIONS = 'list_maxexpressions';

	/**
	 * Contains the specifics of a DBMS.
	 * This is intended to be overridden by inheriting classes.
	 *
	 * @var array
	 */
	protected $specificProperties = array();

	/**
	 * Checks if a specific is defined for the used DBMS.
	 *
	 * @param string $specific
	 * @return bool
	 */
	public function specificExists($specific) {
		return isset($this->specificProperties[$specific]);
	}

	/**
	 * Gets the specific value.
	 *
	 * @param string $specific
	 * @return mixed
	 */
	public function getSpecific($specific) {
		return $this->specificProperties[$specific];
	}

	/**
	 * Splits $expressionList into multiple chunks.
	 *
	 * @param array $expressionList
	 * @param bool $preserveArrayKeys If TRUE, array keys are preserved in array_chunk()
	 * @return array
	 */
	public function splitMaxExpressions($expressionList, $preserveArrayKeys = FALSE) {
		if (!$this->specificExists(self::LIST_MAXEXPRESSIONS)) {
			return array($expressionList);
		}

		return array_chunk($expressionList, $this->getSpecific(self::LIST_MAXEXPRESSIONS), $preserveArrayKeys);
	}

	/**
	 * Transforms a database specific representation of field information and translates it
	 * as close as possible to the MySQL standard.
	 *
	 * @param array $fieldRow
	 * @param string $metaType
	 * @return array
	 */
	public function transformFieldRowToMySQL($fieldRow, $metaType) {
		$mysqlType = $this->getNativeFieldType($metaType);
		$mysqlType .= $this->getNativeFieldLength($mysqlType, $fieldRow['max_length']);

		$fieldRow['Field'] = $fieldRow['name'];
		$fieldRow['Type'] = strtolower($mysqlType);
		$fieldRow['Null'] = $this->getNativeNotNull($fieldRow['not_null']);
		$fieldRow['Key'] = '';
		$fieldRow['Default'] = $fieldRow['default_value'];
		$fieldRow['Extra'] = '';

		return $fieldRow;
	}

	/**
	 * Return actual MySQL type for meta field type
	 *
	 * @param string $metaType Meta type (currenly ADOdb syntax only, http://phplens.com/lens/adodb/docs-adodb.htm#metatype)
	 * @return string Native type as reported as in mysqldump files, uppercase
	 */
	public function getNativeFieldType($metaType) {
		switch (strtoupper($metaType)) {
			case 'C':
				return 'VARCHAR';
			case 'XL':

			case 'X':
				return 'LONGTEXT';
			case 'C2':
				return 'VARCHAR';
			case 'X2':
				return 'LONGTEXT';
			case 'B':
				return 'LONGBLOB';
			case 'D':
				return 'DATE';
			case 'T':
				return 'DATETIME';
			case 'L':
				return 'TINYINT';
			case 'I':

			case 'I1':

			case 'I2':

			case 'I4':

			case 'I8':
				return 'BIGINT';
			case 'F':
				return 'DOUBLE';
			case 'N':
				return 'NUMERIC';
			default:
				return $metaType;
		}
	}

	/**
	 * Return MetaType for native MySQL field type
	 *
	 * @param string $nativeType native type as reported as in mysqldump files
	 * @return string Meta type (currently ADOdb syntax only, http://phplens.com/lens/adodb/docs-adodb.htm#metatype)
	 */
	public function getMetaFieldType($nativeType) {
		switch (strtoupper($nativeType)) {
			case 'STRING':

			case 'CHAR':

			case 'VARCHAR':

			case 'TINYBLOB':

			case 'TINYTEXT':

			case 'ENUM':

			case 'SET':
				return 'C';
			case 'TEXT':

			case 'LONGTEXT':

			case 'MEDIUMTEXT':
				return 'XL';
			case 'IMAGE':

			case 'LONGBLOB':

			case 'BLOB':

			case 'MEDIUMBLOB':
				return 'B';
			case 'YEAR':

			case 'DATE':
				return 'D';
			case 'TIME':

			case 'DATETIME':

			case 'TIMESTAMP':
				return 'T';
			case 'FLOAT':

			case 'DOUBLE':
				return 'F';
			case 'INT':

			case 'INTEGER':

			case 'TINYINT':

			case 'SMALLINT':

			case 'MEDIUMINT':

			case 'BIGINT':
				return 'I8';
			default:
				return 'N';
		}
	}

	/**
	 * Determine the native field length information for a table field.
	 *
	 * @param string  $mysqlType
	 * @param integer $maxLength
	 * @return string
	 */
	public function getNativeFieldLength($mysqlType, $maxLength) {
		if ($maxLength === -1) {
			return '';
		}
		switch ($mysqlType) {
			case 'INT':
				return '(11)';
			default:
				return '(' . $maxLength . ')';
		}
	}

	/**
	 * Return the MySQL native representation of the NOT NULL setting
	 *
	 * @param mixed $notNull
	 * @return string
	 */
	protected function getNativeNotNull($notNull) {
		return (bool)$notNull ? 'NO' : 'YES';
	}
}
