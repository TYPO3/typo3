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
 * Any implementation of this interface must adhere to the following behavior:
 *
 * Two Value instances, v1 and v2, are considered equal if and only if:
 * * v1.getType() == v2.getType(), and,
 * * v1.getString().equals(v2.getString())
 *
 * Actually comparing two Value instances by converting them to string form may not
 * be practical in some cases (for example, if the values are very large binaries).
 * Consequently, the above is intended as a normative definition of Value equality
 * but not as a procedural test of equality. It is assumed that implementations
 * will have efficient means of determining equality that conform with the above
 * definition. An implementation is only required to support equality comparisons on
 * Value instances that were acquired from the same Session and whose contents have
 * not been read. The equality comparison must not change the state of the Value
 * instances even though the getString() method in the above definition implies a
 * state change.
 *
 * The deprecated getStream() method and it's related exceptions and rules have been
 * omitted in this PHP port of the API.
 *
 * @package Extbase
 * @subpackage Persistence
 * @version  $Id: ValueInterface.php 1729 2009-11-25 21:37:20Z stucki $
 */
interface Tx_Extbase_Persistence_ValueInterface {

	/**
	 * Returns a string representation of this value.
	 *
	 * @return string A string representation of the value of this property.
	 * @throws Tx_Extbase_Persistence_Exception_ValueFormatException if conversion to a String is not possible.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if another error occurs.
	 */
	public function getString();

	/**
	 * Returns a Binary representation of this value. The Binary object in turn provides
	 * methods to access the binary data itself. Uses the standard conversion to binary
	 * (see JCR specification).
	 *
	 * @return \F3\PHPCR\BinaryInterface A Binary representation of this value.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if another error occurs.
	 */
	public function getBinary();

	/**
	 * Returns a long representation of this value.
	 *
	 * @return string A long representation of the value of this property.
	 * @throws Tx_Extbase_Persistence_Exception_ValueFormatException if conversion to a long is not possible.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if another error occurs.
	 */
	public function getLong();

	/**
	 * Returns a BigDecimal representation of this value.
	 *
	 * @return string A double representation of the value of this property.
	 * @throws Tx_Extbase_Persistence_Exception_ValueFormatException if conversion is not possible.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if another error occurs.
	 */
	public function getDecimal();

	/**
	 * Returns a double representation of this value.
	 *
	 * @return string A double representation of the value of this property.
	 * @throws Tx_Extbase_Persistence_Exception_ValueFormatException if conversion to a double is not possible.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if another error occurs.
	 */
	public function getDouble();

	/**
	 * Returns a \DateTime representation of this value.
	 *
	 * The object returned is a copy of the stored value, so changes to it are
	 * not reflected in internal storage.
	 *
	 * @return \DateTime A \DateTime representation of the value of this property.
	 * @throws Tx_Extbase_Persistence_Exception_ValueFormatException if conversion to a \DateTime is not possible.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if another error occurs.
	 */
	public function getDate();

	/**
	 * Returns a boolean representation of this value.
	 *
	 * @return string A boolean representation of the value of this property.
	 * @throws Tx_Extbase_Persistence_Exception_ValueFormatException if conversion to a boolean is not possible.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if another error occurs.
	 */
	public function getBoolean();

	/**
	 * Returns the type of this Value. One of:
	 * * PropertyType.STRING
	 * * PropertyType.DATE
	 * * PropertyType.BINARY
	 * * PropertyType.DOUBLE
	 * * PropertyType.LONG
	 * * PropertyType.BOOLEAN
	 * * PropertyType.NAME
	 * * PropertyType.PATH
	 * * PropertyType.REFERENCE
	 * * PropertyType.WEAKREFERENCE
	 * * PropertyType.URI
	 *
	 * The type returned is that which was set at property creation.
	 * @return integer The type of the value
	 */
	public function getType();
}

?>