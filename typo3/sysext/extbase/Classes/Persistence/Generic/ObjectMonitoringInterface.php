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
 * An interface how to monitor changes on an object and its properties. All domain objects which should be persisted need to implement the below interface.
 *
 * @see Tx_Extbase_DomainObject_AbstractEntity
 * @see Tx_Extbase_DomainObject_AbstractValueObject
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $ID:$
 */
interface Tx_Extbase_Persistence_ObjectMonitoringInterface {

	/**
	 * Register an object's clean state, e.g. after it has been reconstituted
	 * from the database
	 *
	 * @return void
	 */
	public function _memorizeCleanState();

	/**
	 * Returns TRUE if the properties were modified after reconstitution
	 *
	 * @return boolean
	 */
	public function _isDirty();
	
}
?>