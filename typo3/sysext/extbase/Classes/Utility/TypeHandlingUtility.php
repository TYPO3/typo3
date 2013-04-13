<?php
namespace TYPO3\CMS\Extbase\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * PHP type handling functions
 *
 */
class TypeHandlingUtility {

	/**
	 * A property type parse pattern.
	 */
	const PARSE_TYPE_PATTERN = '/^\\\\?(?P<type>integer|int|float|double|boolean|bool|string|DateTime|Tx_[a-zA-Z0-9_]+|[A-Z][a-zA-Z0-9\\\\_]+|object|array|ArrayObject|SplObjectStorage|TYPO3\\\\CMS\\\\Extbase\\\\Persistence\\\\ObjectStorage|Tx_Extbase_Persistence_ObjectStorage)(?:<\\\\?(?P<elementType>[a-zA-Z0-9\\\\_]+)>)?/';

	/**
	 * A type pattern to detect literal types.
	 */
	const LITERAL_TYPE_PATTERN = '/^(?:integer|int|float|double|boolean|bool|string)$/';

	/**
	 * @var array
	 */
	static protected $collectionTypes = array('array', 'ArrayObject', 'SplObjectStorage', 'TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', 'Tx_Extbase_Persistence_ObjectStorage');

	/**
	 * Returns an array with type information, including element type for
	 * collection types (array, SplObjectStorage, ...)
	 *
	 * @param string $type Type of the property (see PARSE_TYPE_PATTERN)
	 * @return array An array with information about the type
	 * @throws \TYPO3\CMS\Extbase\Utility\Exception\InvalidTypeException
	 */
	static public function parseType($type) {
		$matches = array();
		if (preg_match(self::PARSE_TYPE_PATTERN, $type, $matches)) {
			$type = self::normalizeType($matches['type']);
			$elementType = isset($matches['elementType']) ? self::normalizeType($matches['elementType']) : NULL;

			if ($elementType !== NULL && !self::isCollectionType($type)) {
				throw new \TYPO3\CMS\Extbase\Utility\Exception\InvalidTypeException('Found an invalid element type declaration in %s. Type "' . $type . '" must not have an element type hint (' . $elementType . ').', 1264093642);
			}

			return array(
				'type' => $type,
				'elementType' => $elementType
			);
		} else {
			throw new \TYPO3\CMS\Extbase\Utility\Exception\InvalidTypeException('Found an invalid element type declaration in %s. A type "' . var_export($type, TRUE) . '" does not exist.', 1264093630);
		}
	}

	/**
	 * Normalize data types so they match the PHP type names:
	 *  int -> integer
	 *  double -> float
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

	/**
	 * Returns TRUE if the $type is a literal.
	 *
	 * @param string $type
	 * @return boolean
	 */
	static public function isLiteral($type) {
		return preg_match(self::LITERAL_TYPE_PATTERN, $type) === 1;
	}

	/**
	 * Returns TRUE if the $type is a simple type.
	 *
	 * @param string $type
	 * @return boolean
	 */
	static public function isSimpleType($type) {
		return in_array(self::normalizeType($type), array('array', 'string', 'float', 'integer', 'boolean'), TRUE);
	}

	/**
	 * Returns TRUE if the $type is a collection type.
	 *
	 * @param string $type
	 * @return boolean
	 */
	static public function isCollectionType($type) {
		if (in_array($type, self::$collectionTypes, TRUE)) {
			return TRUE;
		}

		if (class_exists($type) === TRUE || interface_exists($type) === TRUE) {
			foreach (self::$collectionTypes as $collectionType) {
				if (is_subclass_of($type, $collectionType) === TRUE) {
					return TRUE;
				}
			}

				// is_subclass_of does not check for interfaces in PHP < 5.3.7
			if (version_compare(PHP_VERSION, '5.3.7', '<') === TRUE) {
				foreach (self::$collectionTypes as $collectionType) {
					if (in_array($collectionType, class_implements($type)) === TRUE) {
						return TRUE;
					}
				}
			}
		}

		return FALSE;
	}

	/**
	 * Converts a hex encoded string into binary data
	 *
	 * @param string $hexadecimalData A hex encoded string of data
	 * @return string A binary string decoded from the input
	 */
	static public function hex2bin($hexadecimalData) {
		$binaryData = '';
		$length = strlen($hexadecimalData);
		for ($i = 0; $i < $length; $i += 2) {
			$binaryData .=  pack('C', hexdec(substr($hexadecimalData, $i, 2)));
		}
		return $binaryData;
	}
}

?>