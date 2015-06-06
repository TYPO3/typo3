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
 * This class contains the specifics for PostgreSQL DBMS.
 * Any logic is in AbstractSpecifics.
 */
class PostgresSpecifics extends AbstractSpecifics {
	/**
	 * Contains the DBMS specific mapping overrides for native MySQL to ADOdb meta field types
	 */
	protected $nativeToMetaFieldTypeOverrides = array(
		'TINYBLOB' => 'B',
		'INT' => 'I4',
		'INTEGER' => 'I4',
		'TINYINT' => 'I2',
		'SMALLINT' => 'I2',
		'MEDIUMINT' => 'I4'
	);

	/**
	 * Contains the DBMS specific mapping information for ADOdb meta field types to MySQL native field types
	 *
	 * @var array
	 */
	protected $metaToNativeFieldTypeOverrides = array(
		'R' => 'INT',
		'I' => 'INT',
		'I1' => 'SMALLINT',
		'I2' => 'SMALLINT',
		'I4' => 'INT',
	);

	/**
	 * Determine the native field length information for a table field.
	 *
	 * @param string $mysqlType
	 * @param integer $maxLength
	 * @return string
	 */
	public function getNativeFieldLength($mysqlType, $maxLength) {
		if ($maxLength === -1) {
			return '';
		}
		switch ($mysqlType) {
			case 'DOUBLE':
				return '';
			case 'TINYINT':
				return '(4)';
			case 'SMALLINT':
				return '(6)';
			case 'MEDIUMINT':
				return '(9)';
			case 'INT':
				return '(11)';
			case 'BIGINT':
				return '(20)';
			default:
				return '(' . $maxLength . ')';
		}
	}
}
