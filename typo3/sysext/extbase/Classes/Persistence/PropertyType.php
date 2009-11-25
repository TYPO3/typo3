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
 * The property types supported by the JCR standard.
 *
 * The STRING property type is used to store strings.
 * BINARY properties are used to store binary data.
 * The LONG property type is used to store integers.
 * The DECIMAL property type is used to store precise decimal numbers.
 * The DOUBLE property type is used to store floating point numbers.
 * The DATE property type is used to store time and date information. See 4.2.6.1 Date in the specification.
 * The BOOLEAN property type is used to store boolean values.
 * A NAME is a pairing of a namespace and a local name. When read, the namespace is mapped to the current prefix. See 4.2.6.2 Name in the specification.
 * A PATH property is an ordered list of path elements. A path element is a NAME with an optional index. When read, the NAMEs within the path are mapped to their current prefix. A path may be absolute or relative. See 4.2.6.3 Path in the specification.
 * A REFERENCE property stores the identifier of a referenceable node (one having type mix:referenceable), which must exist within the same workspace or session as the REFERENCE property. A REFERENCE property enforces this referential integrity by preventing (in level 2 implementations) the removal of its target node. See 4.2.6.4 Reference in the specification.
 * A WEAKREFERENCE property stores the identifier of a referenceable node (one having type mix:referenceable). A WEAKREFERENCE property does not enforce referential integrity. See 4.2.6.5 Weak Reference in the specification.
 * A URI property is identical to STRING property except that it only accepts values that conform to the syntax of a URI-reference as defined in RFC 3986. See also 4.2.6.6 URI in the specification.
 * UNDEFINED can be used within a property definition (see 4.7.5 Property Definitions) to specify that the property in question may be of any type. However, it cannot be the actual type of any property instance. For example it will never be returned by Property.getType() and (in level 2 implementations) it cannot be assigned as the type when creating a new property.
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: PropertyType.php 1729 2009-11-25 21:37:20Z stucki $
 */
final class Tx_Extbase_Persistence_PropertyType {

	/**
	 * This constant can be used within a property definition to specify that
	 * the property in question may be of any type.
	 * However, it cannot be the actual type of any property instance. For
	 * example, it will never be returned by Property#getType and it cannot be
	 * assigned as the type when creating a new property.
	 */
	const UNDEFINED = 0;

	/**
	 * The STRING property type is used to store strings.
	 */
	const STRING = 1;

	/**
	 * BINARY properties are used to store binary data.
	 */
	const BINARY = 2;

	/**
	 * The LONG property type is used to store integers.
	 */
	const LONG = 3;

	/**
	 * The DOUBLE property type is used to store floating point numbers.
	 */
	const DOUBLE = 4;

	/**
	 * The DATE property type is used to store time and date information.
	 */
	const DATE = 5;

	/**
	 * The BOOLEAN property type is used to store boolean values.
	 */
	const BOOLEAN = 6;

	/**
	 * A NAME is a pairing of a namespace and a local name. When read, the
	 * namespace is mapped to the current prefix.
	 *
	 * WE DO NOT USE THIS IN EXTBASE!
	 */
	const NAME = 7;

	/**
	 * A PATH property is an ordered list of path elements. A path element is a
	 * NAME with an optional index. When read, the NAMEs within the path are
	 * mapped to their current prefix. A path may be absolute or relative.
	 *
	 * WE DO NOT USE THIS IN EXTBASE!
	 */
	const PATH = 8;

	/**
	 * A REFERENCE property stores the identifier of a referenceable node (one
	 * having type mix:referenceable), which must exist within the same
	 * workspace or session as the REFERENCE property. A REFERENCE property
	 * enforces this referential integrity by preventing the removal of its
	 * target node.
	 */
	const REFERENCE = 9;

	/**
	 * A WEAKREFERENCE property stores the identifier of a referenceable node
	 * (one having type mix:referenceable). A WEAKREFERENCE property does not
	 * enforce referential integrity.
	 *
	 * WE DO NOT USE THIS IN EXTBASE!
	 */
	const WEAKREFERENCE = 10;

	/**
	 * A URI property is identical to STRING property except that it only
	 * accepts values that conform to the syntax of a URI-reference as defined
	 * in RFC 3986.
	 *
	 * WE DO NOT USE THIS IN EXTBASE!
	 */
	const URI = 11;

	/**
	 * The DECIMAL property type is used to store precise decimal numbers.
	 *
	 * WE DO NOT USE THIS IN EXTBASE!
	 */
	const DECIMAL = 12;

	/**
	 * The INTEGER property type is used to store precise decimal numbers.
	 *
	 * WE DO NOT USE THIS IN EXTBASE!
	 */
	const INTEGER = 13;

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_UNDEFINED = 'undefined';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_STRING = 'String';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_BINARY = 'Binary';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_LONG = 'Long';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_DOUBLE = 'Double';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_DATE = 'Date';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_BOOLEAN = 'Boolean';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_NAME = 'Name';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_PATH = 'Path';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_REFERENCE = 'Reference';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_WEAKREFERENCE = 'WeakReference';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_URI= 'URI';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_DECIMAL = 'Decimal';

	/**
	 * String constant for type name as used in serialization.
	 */
	const TYPENAME_INTEGER = 'Integer';

	/**
	 * Make instantiation impossible...
	 *
	 * @return void
	 */
	private function __construct() {}

	/**
	 * Returns the name of the specified type, as used in serialization.
	 *
	 * @param integer $type type the property type
	 * @return string  name of the specified type
	 */
	static public function nameFromValue($type) {
		switch (intval($type)) {
			case self::UNDEFINED :
				return self::TYPENAME_UNDEFINED;
				break;
			case self::STRING :
				return self::TYPENAME_STRING;
				break;
			case self::BINARY :
				return self::TYPENAME_BINARY;
				break;
			case self::BOOLEAN :
				return self::TYPENAME_BOOLEAN;
				break;
			case self::LONG :
				return self::TYPENAME_LONG;
				break;
			case self::DOUBLE :
				return self::TYPENAME_DOUBLE;
				break;
			case self::DECIMAL :
				return self::TYPENAME_DECIMAL;
				break;
			case self::INTEGER :
				return self::TYPENAME_INTEGER;
				break;
			case self::DATE :
				return self::TYPENAME_DATE;
				break;
			case self::NAME :
				return self::TYPENAME_NAME;
				break;
			case self::PATH :
				return self::TYPENAME_PATH;
				break;
			case self::REFERENCE :
				return self::TYPENAME_REFERENCE;
				break;
			case self::WEAKREFERENCE :
				return self::TYPENAME_WEAKREFERENCE;
				break;
			case self::URI :
				return self::TYPENAME_URI;
				break;
		}
	}


	/**
	 * Returns the numeric constant value of the type with the specified name.
	 *
	 * @param string $name The name of the property type
	 * @return int The numeric constant value
	 */
	static public function valueFromName($name) {
		switch ($name) {
			case self::TYPENAME_UNDEFINED :
				return self::UNDEFINED;
				break;
			case self::TYPENAME_STRING :
				return self::STRING;
				break;
			case self::TYPENAME_BINARY :
				return self::BINARY;
				break;
			case self::TYPENAME_LONG :
				return self::LONG;
				break;
			case self::TYPENAME_DOUBLE :
				return self::DOUBLE;
				break;
			case self::TYPENAME_DECIMAL :
				return self::DECIMAL;
				break;
			case self::TYPENAME_INTEGER :
				return self::INTEGER;
				break;
			case self::TYPENAME_DATE :
				return self::DATE;
				break;
			case self::TYPENAME_BOOLEAN :
				return self::BOOLEAN;
				break;
			case self::TYPENAME_NAME :
				return self::NAME;
				break;
			case self::TYPENAME_PATH :
				return self::PATH;
				break;
			case self::TYPENAME_REFERENCE :
				return self::REFERENCE;
				break;
			case self::TYPENAME_WEAKREFERENCE :
				return self::WEAKREFERENCE;
				break;
			case self::TYPENAME_URI :
				return self::URI;
				break;
		}
	}

	/**
	 * Returns the numeric constant value of the type for the given PHP type
	 * name as returned by gettype().
	 *
	 * @param string $type
	 * @return integer
	 */
	static public function valueFromType($type) {
		switch (strtolower($type)) {
			case 'string':
				return Tx_Extbase_Persistence_PropertyType::STRING;
				break;
			case 'boolean':
				return Tx_Extbase_Persistence_PropertyType::BOOLEAN;
				break;
			case 'integer':
				return Tx_Extbase_Persistence_PropertyType::LONG;
				break;
			case 'float':
			case 'double':
				return Tx_Extbase_Persistence_PropertyType::DOUBLE;
				break;
			case 'integer':
			case 'int':
				return Tx_Extbase_Persistence_PropertyType::INTEGER;
				break;
			case 'datetime':
				return Tx_Extbase_Persistence_PropertyType::DATE;
				break;
			default:
				return Tx_Extbase_Persistence_PropertyType::UNDEFINED;
		}
	}
}

?>