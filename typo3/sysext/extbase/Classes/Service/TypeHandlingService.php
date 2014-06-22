<?php
namespace TYPO3\CMS\Extbase\Service;

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
