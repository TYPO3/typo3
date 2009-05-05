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
 * A data mapper interface.
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 */
interface Tx_Extbase_Persistence_DataMapperInterface {

	/**
	 * Sets the aggregate root objects. The aggregate root objects are a starting point to traverse the 
	 * object graph.
	 *
	 * @param Tx_Extbase_Persistence_ObjectStorage $objects The objects to be registered
	 * @return void
	 */
	public function setAggregateRootObjects(Tx_Extbase_Persistence_ObjectStorage $objects);
	
	/**
	 * Sets the deleted objects.
	 *
	 * @param Tx_Extbase_Persistence_ObjectStorage $objects The objects to be deleted
	 * @return void
	 */
	public function setDeletedObjects(Tx_Extbase_Persistence_ObjectStorage $objects);

	/**
	 * Persists all objects traversing the object graph.
	 *
	 * @return void
	 */
	public function persistObjects();

	/**
	 * Processes all deleted objects.
	 *
	 * @return void
	 */
	public function processDeletedObjects();

}
?>