<?php
namespace TYPO3\CMS\Dbal\Database\Specifics;

/**
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
}