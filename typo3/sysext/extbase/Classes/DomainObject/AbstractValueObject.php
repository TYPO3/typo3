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
 * @package Extbase
 * @subpackage DomainObject
 * @version $ID:$
 */
abstract class Tx_Extbase_DomainObject_AbstractValueObject extends Tx_Extbase_DomainObject_AbstractDomainObject {

	/**
	 * Returns the value of the Value Object. Must be overwritten by a concrete value object.
	 *
	 * @return void
	 */
	public function getValue() {
		return $this->__toString();
	}
	
	/**
	 * Clone method. Sets the _isClone property.
	 *
	 * @return void
	 */
	public function __clone() {
		$this->uid = NULL;
	}

}
?>