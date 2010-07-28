<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Christian Müller <christian@kitsunet.de>
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
 * PHP type handling functions
 *
 * @package Extbase
 * @subpackage Utility
 * @version $ID:$
 * @api
 */
class Tx_Extbase_Utility_TypeHandling {

	/**
	 * A property type parse pattern.
	 */
	const PARSE_TYPE_PATTERN = '/^\\\\?(?P<type>integer|int|float|double|boolean|bool|string|DateTime|Tx_[a-zA-Z0-9_]+|array|ArrayObject|SplObjectStorage)(?:<(?P<elementType>[a-zA-Z0-9_]+)>)?/';

	/**
	 * Adds (defines) a specific property and its type.
	 *
	 * @param string $type Type of the property (see PARSE_TYPE_PATTERN)
	 * @return array An array with information about the type
	 */
	static public function parseType($type) {
		$matches = array();
		if (preg_match(self::PARSE_TYPE_PATTERN, $type, $matches)) {
			$type = self::normalizeType($matches['type']);
			$elementType = isset($matches['elementType']) ? self::normalizeType($matches['elementType']) : NULL;

			if ($elementType !== NULL && !in_array($type, array('array', 'ArrayObject', 'SplObjectStorage', 'Tx_Extbase_Persistence_ObjectStorage'))) {
				throw new InvalidArgumentException('Type "' . $type . '" must not have an element type hint (' . $elementType . ').', 1264093642);
			}

			return array(
				'type' => $type,
				'elementType' => $elementType
			);
		} else {
			throw new InvalidArgumentException('Invalid type encountered: ' . var_export($type, TRUE), 1264093630);
		}
	}

	/**
	 * Normalize data types so they match the PHP type names:
	 *  int -> integer
	 *  float -> double
	 *  bool -> boolean
	 *
	 * @param string $type Data type to unify
	 * @return string unified data type
	 */
	static public function normalizeType($type) {
		switch ($type) {
			case 'int':
				$type = 'integer';
				break;
			case 'bool':
				$type = 'boolean';
				break;
			case 'double':
				$type = 'float';
				break;
		}
		return $type;
	}
	
}
?>