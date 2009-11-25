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
 * TYPO3 Query settings interface. This interfaceis NOT part of the FLOW3 API.
 * It reflects the settings unique to TYPO3 4.x.
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: Typo3QuerySettingsInterface.php 1729 2009-11-25 21:37:20Z stucki $
 * @api
 */
interface Tx_Extbase_Persistence_Typo3QuerySettingsInterface extends Tx_Extbase_Persistence_QuerySettingsInterface {

	/**
	 * Sets the flag if the storage page should be respected for the query.
	 *
	 * @param $respectStoragePage If TRUE the storage page ID will be determined and the statement will be extended accordingly.
	 * @return $this (fluent interface)
	 * @api
	 */
	public function setRespectStoragePage($respectStoragePage);

	/**
	 * Sets the flag if the visibility in the frontend should be respected.
	 *
	 * @param $respectEnableFields TRUE if the visibility in the frontend should be respected. If TRUE, the "enable fields" of TYPO3 will be added to the query statement.
	 * @return $this (fluent interface)
	 * @api
	 */
	public function setRespectEnableFields($respectEnableFields);

	/**
	 * Sets the state, if the QueryResult should be returned unmapped.
	 *
	 * @return boolean TRUE, if the QueryResult should be returned unmapped; otherwise FALSE.
	 */
	public function setReturnRawQueryResult($returnRawQueryResult);
	
	/**
	 * Returns the state, if the QueryResult should be returned unmapped.
	 *
	 * @return boolean TRUE, if the QueryResult should be returned unmapped; otherwise FALSE.
	 */
	public function getReturnRawQueryResult();
	
}
?>