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
 * The ValueFactory object provides methods for the creation Value objects that can
 * then be used to set properties.
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: ValueFactoryInterface.php 1729 2009-11-25 21:37:20Z stucki $
 */
interface Tx_Extbase_Persistence_ValueFactoryInterface {

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
	 * @param boolean $weak When a Node is given as $value this can be given as TRUE to create a WEAKREFERENCE
	 * @return Tx_Extbase_Persistence_ValueInterface
	 * @throws Tx_Extbase_Persistence_Exception_ValueFormatException is thrown if the specified value cannot be converted to the specified type.
	 * @throws Tx_Extbase_Persistence_Exception_RepositoryException if the specified Node is not referenceable, the current Session is no longer active, or another error occurs.
	 * @throws IllegalArgumentException if the specified DateTime value cannot be expressed in the ISO 8601-based format defined in the JCR 2.0 specification and the implementation does not support dates incompatible with that format.
	 */
	public function createValue($value, $type = NULL, $weak = FALSE);

}

?>