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
 * A abstract Value Object. A Value Object is an object that describes some characteristic
 * or attribute (e.g. a color) but carries no concept of identity.
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 */
abstract class Tx_Extbase_DomainObject_AbstractValueObject extends Tx_Extbase_DomainObject_AbstractDomainObject {

	/**
	 * Register an object's clean state, e.g. after it has been reconstituted
	 * from the database
	 *
	 * @return void
	 * @internal
	 */
	public function _memorizeCleanState() {
	}

	/**
	 * Returns a hash map of dirty properties and $values. This is always the empty array for ValueObjects, because ValueObjects never change.
	 *
	 * @return array
	 * @internal
	 */
	public function _getDirtyProperties() {
		return array();
	}

	/**
	 * Returns TRUE if the properties were modified after reconstitution. However, value objects can be never updated.
	 *
	 * @return boolean
	 * @internal
	 */
	public function _isDirty() {
		return FALSE;
	}
}
?>