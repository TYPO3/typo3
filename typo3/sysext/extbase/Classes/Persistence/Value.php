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
 * A generic holder for the value of a property. A Value object can be used
 * without knowing the actual property type (STRING, DOUBLE, BINARY etc.).
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: Value.php 1729 2009-11-25 21:37:20Z stucki $
 * @scope prototype
 */
class Tx_Extbase_Persistence_Value implements Tx_Extbase_Persistence_ValueInterface {

	/**
	 * @var mixed
	 */
	protected $value;

	/**
	 * @var integer
	 */
	protected $type;

	/**
	 * Constructs a Value object from the given $value and $type arguments
	 *
	 * @param mixed $value The value of the Value object
	 * @param integer $type A type, see constants in \F3\PHPCR\PropertyType
	 * @return void
	 */
	public function __construct($value, $type) {
		$this->value = $value;
		$this->type = $type;
	}

	/**
	 * Returns a string representation of this value. For Value objects being
	 * of type DATE the string will conform to ISO8601 format.
	 *
	 * @return string A String representation of the value of this property.
	 */
	public function getString() {
		if ($this->value === NULL) return NULL;
		if (is_array($this->value)) return $this->value;

		switch ($this->type) {
			case Tx_Extbase_Persistence_PropertyType::DATE:
				if (is_a($this->value, 'DateTime')) {
					return $this->value->format('U');
				} else {
					$this->value = new DateTime($this->value);
					return $this->value->format('U');
				}
			case Tx_Extbase_Persistence_PropertyType::BOOLEAN:
				return (string)(int)$this->value;
			default:
				return (string)$this->value;
		}
	}

	/**
	 * Returns the value as string, alias for getString()
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->getString();
	}

	/**
	 * Returns a Binary representation of this value. The Binary object in turn provides
	 * methods to access the binary data itself. Uses the standard conversion to binary
	 * (see JCR specification).
	 *
	 * @return \F3\TYPO3CR\Binary A Binary representation of this value.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if another error occurs.
	 */
	public function getBinary() {
		throw new Tx_Extbase_Persistence_Exception_UnsupportedMethod('Method not yet implemented, sorry!', 1217843676);
	}

	/**
	 * Returns a long (integer) representation of this value.
	 *
	 * @return string A long representation of the value of this property.
	 * @throws Tx_Extbase_Persistence_Exception_ValueFormatException if conversion to a long is not possible.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if another error occurs.
	 */
	public function getLong() {
		return (int)$this->value;
	}

	/**
	 * Returns a BigDecimal representation of this value (aliased to getDouble()).
	 *
	 * @return float A double representation of the value of this property.
	 * @throws Tx_Extbase_Persistence_Exception_ValueFormatException if conversion is not possible.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if another error occurs.
	 */
	public function getDecimal() {
		return $this->getDouble();
	}

	/**
	 * Returns a double (floating point) representation of this value.
	 *
	 * @return float A double representation of the value of this property.
	 * @throws Tx_Extbase_Persistence_Exception_ValueFormatException if conversion to a double is not possible.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if another error occurs.
	 */
	public function getDouble() {
		return (double)$this->value;
	}

	/**
	 * Returns a DateTime representation of this value.
	 *
	 * @return DateTime A DateTime representation of the value of this property.
	 * @throws Tx_Extbase_Persistence_Exception_ValueFormatException if conversion to a \DateTime is not possible.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if another error occurs.
	 */
	public function getDate() {
		if (is_a($this->value, 'DateTime')) {
			return clone($this->value);
		}

		try {
			return new DateTime($this->value);
		} catch (Exception $e) {
			throw new Tx_Extbase_Persistence_Exception_ValueFormatException('Conversion to a DateTime object is not possible. Cause: ' . $e->getMessage(), 1190034628);
		}
	}

	/**
	 * Returns a boolean representation of this value.
	 *
	 * @return string A boolean representation of the value of this property.
	 * @throws Tx_Extbase_Persistence_Exception_ValueFormatException if conversion to a boolean is not possible.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if another error occurs.
	 */
	public function getBoolean() {
		return (boolean)$this->value;
	}

	/**
	 * Returns the type of this Value. One of:
	 * * \F3\PHPCR\PropertyType::STRING
	 * * \F3\PHPCR\PropertyType::DATE
	 * * \F3\PHPCR\PropertyType::BINARY
	 * * \F3\PHPCR\PropertyType::DOUBLE
	 * * \F3\PHPCR\PropertyType::DECIMAL
	 * * \F3\PHPCR\PropertyType::LONG
	 * * \F3\PHPCR\PropertyType::BOOLEAN
	 * * \F3\PHPCR\PropertyType::NAME
	 * * \F3\PHPCR\PropertyType::PATH
	 * * \F3\PHPCR\PropertyType::REFERENCE
	 * * \F3\PHPCR\PropertyType::WEAKREFERENCE
	 * * \F3\PHPCR\PropertyType::URI
	 *
	 * The type returned is that which was set at property creation.
	 * @return integer The type of the value
	 */
	public function getType() {
		return $this->type;
	}

}

?>