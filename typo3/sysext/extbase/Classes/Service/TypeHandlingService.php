<?php
namespace TYPO3\CMS\Extbase\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Extbase\Utility\TypeHandlingUtility;

/**
 * PHP type handling functions
 * @deprecated since 6.1, will be removed two versions later
 */
class TypeHandlingService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * A property type parse pattern.
	 */
	const PARSE_TYPE_PATTERN = TypeHandlingUtility::PARSE_TYPE_PATTERN;

	/**
	 * A type pattern to detect literal types.
	 */
	const LITERAL_TYPE_PATTERN = TypeHandlingUtility::LITERAL_TYPE_PATTERN;

	/**
	 * Adds (defines) a specific property and its type.
	 *
	 * @param string $type Type of the property (see PARSE_TYPE_PATTERN)
	 * @throws \InvalidArgumentException
	 * @return array An array with information about the type
	 */
	public function parseType($type) {
		return TypeHandlingUtility::parseType($type);
	}

	/**
	 * Normalize data types so they match the PHP type names:
	 * int -> integer
	 * float -> double
	 * bool -> boolean
	 *
	 * @param string $type Data type to unify
	 * @return string unified data type
	 */
	public function normalizeType($type) {
		return TypeHandlingUtility::normalizeType($type);
	}

	/**
	 * Returns TRUE if the $type is a literal.
	 *
	 * @param string $type
	 * @return boolean
	 */
	public function isLiteral($type) {
		return TypeHandlingUtility::isLiteral($type);
	}

	/**
	 * Returns TRUE if the $type is a simple type.
	 *
	 * @param string $type
	 * @return boolean
	 */
	public function isSimpleType($type) {
		return TypeHandlingUtility::isSimpleType($type);
	}
}

?>