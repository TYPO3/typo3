<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * A ValueFactory, used to create Value objects.
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: ValueFactory.php 1729 2009-11-25 21:37:20Z stucki $
 * @scope prototype
 */
class Tx_Extbase_Persistence_ValueFactory implements Tx_Extbase_Persistence_ValueFactoryInterface {

	/**
	 * Returns a Value object with the specified value. If $type is given,
	 * conversion is attempted before creating the Value object.
	 *
	 * If no type is given, the value is stored as is, i.e. it's type is
	 * preserved. Exceptions are:
	 * * if the given $value is a Node object, it's Identifier is fetched for the
	 *   Value object and the type of that object will be REFERENCE
	 * * if the given $value is a Node object, it's Identifier is fetched for the
	 *   Value object and the type of that object will be WEAKREFERENCE if $weak
	 *   is set to TRUE
	 * * if the given $Value is a \DateTime object, the Value type will be DATE.
	 *
	 * @param mixed $value The value to use when creating the Value object
	 * @param integer $type Type request for the Value object
	 * @param boolean $weak When a Node is given as $value this can be given as TRUE to create a WEAKREFERENCE, $type is ignored in that case!
	 * @return \F3\PHPCR\ValueInterface
	 * @throws Tx_Extbase_Persistence_Exception_ValueFormatException is thrown if the specified value cannot be converted to the specified type.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the specified Node is not referenceable, the current Session is no longer active, or another error occurs.
	 * @throws \IllegalArgumentException if the specified DateTime value cannot be expressed in the ISO 8601-based format defined in the JCR 2.0 specification and the implementation does not support dates incompatible with that format.
	 */
	public function createValue($value, $type = Tx_Extbase_Persistence_PropertyType::UNDEFINED, $weak = FALSE) {
		if (is_array($value) && array_key_exists('uid', $value)) {
			$value = $value['uid'];
		}

		if ($type === Tx_Extbase_Persistence_PropertyType::UNDEFINED) {
			return t3lib_div::makeInstance('Tx_Extbase_Persistence_Value', $value, self::guessType($value));
		} else {
			return $this->createValueWithGivenType($value, $type);
		}
	}

	/**
	 * Returns a Value object with the specified value. Conversion from string
	 * is attempted before creating the Value object.
	 *
	 * @param mixed $value
	 * @param integer $type
	 * @return \F3\PHPCR\ValueInterface
	 * @throws Tx_Extbase_Persistence_Exception_ValueFormatException is thrown if the specified value cannot be converted to the specified type.
	 */
	protected function createValueWithGivenType($value, $type) {
		switch ($type) {
			case Tx_Extbase_Persistence_PropertyType::DATE:
				try {
					$value = new DateTime($value);
				} catch (Exception $e) {
					throw new Tx_Extbase_Persistence_Exception_ValueFormatException('The given value could not be converted to a DateTime object.', 1211372741);
				}
				break;
			case Tx_Extbase_Persistence_PropertyType::BINARY:
					// we do not do anything here, getBinary on Value objects does the hard work
				break;
			case Tx_Extbase_Persistence_PropertyType::DECIMAL:
			case Tx_Extbase_Persistence_PropertyType::DOUBLE:
				$value = (float)$value;
				break;
			case Tx_Extbase_Persistence_PropertyType::BOOLEAN:
				$value = (boolean)$value;
				break;
			case Tx_Extbase_Persistence_PropertyType::LONG:
				$value = (int)$value;
				break;
		}
		return t3lib_div::makeInstance('Tx_Persistence_Value', $value, $type);
	}

	/**
	 * Guesses the type for the given value
	 *
	 * @param mixed $value
	 * @return integer
	 * @todo Check type guessing/conversion when we go for PHP6
	 */
	public static function guessType($value) {
		$type = Tx_Extbase_Persistence_PropertyType::UNDEFINED;

		if ($value instanceof DateTime) {
			$type = Tx_Extbase_Persistence_PropertyType::DATE;
		} elseif (is_double($value)) {
			$type = Tx_Extbase_Persistence_PropertyType::DOUBLE;
		} elseif (is_bool($value)) {
			$type = Tx_Extbase_Persistence_PropertyType::BOOLEAN;
		} elseif (is_long($value)) {
			$type = Tx_Extbase_Persistence_PropertyType::LONG;
		} elseif (is_string($value)) {
			$type = Tx_Extbase_Persistence_PropertyType::STRING;
		}

		return $type;
	}
}

?>