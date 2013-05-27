<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
 * A Domain Object Interface. All domain objects which should be persisted need to implement the below interface.
 * Usually you will need to subclass Tx_Extbase_DomainObject_AbstractEntity and Tx_Extbase_DomainObject_AbstractValueObject
 * instead.
 *
 * @see Tx_Extbase_DomainObject_AbstractEntity
 * @see Tx_Extbase_DomainObject_AbstractValueObject
 *
 * @package Extbase
 * @subpackage DomainObject
 * @version $ID:$
 */
interface Tx_Extbase_DomainObject_DomainObjectInterface {

	/**
	 * Getter for uid.
	 *
	 * @return int the uid or NULL if none set yet.
	 */
	public function getUid();

	/**
	 * Returns TRUE if the object is new (the uid was not set, yet). Only for internal use
	 *
	 * @return boolean
	 */
	public function _isNew();

	/**
	 * Reconstitutes a property. Only for internal use.
	 *
	 * @param string $propertyName
	 * @param string $value
	 * @return void
	 */
	public function _setProperty($propertyName, $value);

	/**
	 * Returns the property value of the given property name. Only for internal use.
	 *
	 * @return mixed The propertyValue
	 */
	public function _getProperty($propertyName);

	/**
	 * Returns a hash map of property names and property values
	 *
	 * @return array The properties
	 */
	public function _getProperties();

}
?>