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
 * A Domain Object Interface
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 */
interface Tx_ExtBase_DomainObject_DomainObjectInterface {

	/**
	 * Reconstitutes a property. This method should only be called at reconstitution time!
	 *
	 * @param string $propertyName
	 * @param string $value
	 * @return void
	 * @internal
	 */
	public function _reconstituteProperty($propertyName, $value);

	/**
	 * Register an object's clean state, e.g. after it has been reconstituted
	 * from the database
	 *
	 * @return void
	 * @internal
	 */
	public function _memorizeCleanState();

	/**
	 * Returns TRUE if the properties were modified after reconstitution
	 *
	 * @return boolean
	 * @internal
	 */
	public function _isDirty();
	/**
	 * Returns a hash map of property names and property values
	 *
	 * @return array The properties
	 * @internal
	 */
	public function _getProperties();
	/**
	 * Returns a hash map of dirty properties and $values
	 *
	 * @return boolean
	 * @internal
	 */
	public function _getDirtyProperties();

}
?>